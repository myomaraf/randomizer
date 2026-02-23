<?php

namespace App\Http\Controllers;

use App\Models\Raffle;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

            $tickets = $raffle->tickets()
                ->orderBy('position')
                ->paginate(100)
                ->withQueryString();

            return view('raffles.show', [
                'raffle' => $raffle,
                'tickets' => $tickets,
            ]);
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('raffles.index', ['rafflee' => $raffle_id])
                ->with('toast_error', 'Unable to load this raffle right now. Please try again.');
        }
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
