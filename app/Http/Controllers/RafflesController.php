<?php

namespace App\Http\Controllers;

use App\Models\Raffle;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class RafflesController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $raffleId = $this->extractRaffleId($request);

        if ($raffleId !== '' && ! $request->session()->has('toast_error')) {
            return redirect()->route('raffles.show', ['raffle_id' => $raffleId]);
        }

        return view('raffles.index');
    }

    public function show(string $raffle_id): View|RedirectResponse
    {
        try {
            $raffle = Raffle::query()
                ->where('raffle_id', $raffle_id)
                ->first();

            if ($raffle === null) {
                return redirect()
                    ->route('raffles.index', ['rafflee' => $raffle_id])
                    ->with('toast_error', "Raffle ID '{$raffle_id}' does not exist.");
            }

            return view('raffles.show', [
                'raffle' => $raffle,
            ]);
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('raffles.index', ['rafflee' => $raffle_id])
                ->with('toast_error', 'Unable to load this raffle right now. Please try again.');
        }
    }

    public function ticketsData(Request $request, string $raffle_id): JsonResponse
    {
        $raffle = Raffle::query()
            ->where('raffle_id', $raffle_id)
            ->first();

        if ($raffle === null) {
            return response()->json([
                'message' => "Raffle ID '{$raffle_id}' does not exist.",
            ], 404);
        }

        $draw = max(1, (int) $request->input('draw', 1));
        $start = max(0, (int) $request->input('start', 0));
        $requestedLength = (int) $request->input('length', 100);
        $length = $requestedLength === -1 ? 1000 : min(max($requestedLength, 1), 1000);
        $search = trim((string) $request->input('search.value', ''));

        $orderColumns = [
            0 => 'position',
            1 => 'uuid',
        ];
        $orderColumnIndex = (int) $request->input('order.0.column', 0);
        $orderColumn = $orderColumns[$orderColumnIndex] ?? 'position';
        $orderDirection = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc'
            ? 'desc'
            : 'asc';

        $ticketsQuery = $raffle->tickets()->select(['position', 'uuid']);

        if ($search !== '') {
            $ticketsQuery->where(function ($query) use ($search): void {
                $query->where('uuid', 'like', '%' . $search . '%');

                if (ctype_digit($search)) {
                    $query->orWhere('position', (int) $search);
                }
            });
        }

        $recordsTotal = max(0, (int) $raffle->count);
        $recordsFiltered = $search === ''
            ? $recordsTotal
            : (clone $ticketsQuery)->count();

        $tickets = $ticketsQuery
            ->orderBy($orderColumn, $orderDirection)
            ->offset($start)
            ->limit($length)
            ->get();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $tickets->map(static function ($ticket): array {
                return [
                    'position' => $ticket->position,
                    'uuid' => $ticket->uuid,
                ];
            })->values(),
        ]);
    }

    public function exportTicketsCsv(string $raffle_id): StreamedResponse|RedirectResponse
    {
        $raffle = Raffle::query()
            ->where('raffle_id', $raffle_id)
            ->first();

        if ($raffle === null) {
            return redirect()
                ->route('raffles.index', ['rafflee' => $raffle_id])
                ->with('toast_error', "Raffle ID '{$raffle_id}' does not exist.");
        }

        $safeRaffleId = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $raffle->raffle_id) ?: 'raffle';
        $filename = "tickets_{$safeRaffleId}.csv";

        return response()->streamDownload(function () use ($raffle): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['position', 'uuid']);

            foreach (
                $raffle->tickets()
                    ->select(['position', 'uuid'])
                    ->orderBy('position')
                    ->cursor() as $ticket
            ) {
                fputcsv($handle, [$ticket->position, $ticket->uuid]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function exportTicketsExcel(string $raffle_id): StreamedResponse|RedirectResponse
    {
        $raffle = Raffle::query()
            ->where('raffle_id', $raffle_id)
            ->first();

        if ($raffle === null) {
            return redirect()
                ->route('raffles.index', ['rafflee' => $raffle_id])
                ->with('toast_error', "Raffle ID '{$raffle_id}' does not exist.");
        }

        $safeRaffleId = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $raffle->raffle_id) ?: 'raffle';
        $filename = "tickets_{$safeRaffleId}.xls";

        return response()->streamDownload(function () use ($raffle): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "<?xml version=\"1.0\"?>\n");
            fwrite($handle, "<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\" ");
            fwrite($handle, "xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\">\n");
            fwrite($handle, "<Worksheet ss:Name=\"Tickets\"><Table>\n");
            fwrite($handle, "<Row>");
            fwrite($handle, "<Cell><Data ss:Type=\"String\">position</Data></Cell>");
            fwrite($handle, "<Cell><Data ss:Type=\"String\">uuid</Data></Cell>");
            fwrite($handle, "</Row>\n");

            foreach (
                $raffle->tickets()
                    ->select(['position', 'uuid'])
                    ->orderBy('position')
                    ->cursor() as $ticket
            ) {
                $position = (int) $ticket->position;
                $uuid = htmlspecialchars((string) $ticket->uuid, ENT_XML1 | ENT_QUOTES, 'UTF-8');

                fwrite($handle, "<Row>");
                fwrite($handle, "<Cell><Data ss:Type=\"Number\">{$position}</Data></Cell>");
                fwrite($handle, "<Cell><Data ss:Type=\"String\">{$uuid}</Data></Cell>");
                fwrite($handle, "</Row>\n");
            }

            fwrite($handle, "</Table></Worksheet></Workbook>");
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    private function extractRaffleId(Request $request): string
    {
        $rafflee = trim((string) $request->query('rafflee', ''));

        if ($rafflee !== '') {
            return $rafflee;
        }

        return trim((string) $request->query('raffle_id', ''));
    }
}
