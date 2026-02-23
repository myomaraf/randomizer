<?php

namespace App\Http\Controllers;

use App\Models\Raffle;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RafflesController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $raffleId = trim((string) $request->query('raffle_id', ''));

        if ($raffleId !== '') {
            return redirect()->route('raffles.show', ['raffle_id' => $raffleId]);
        }

        return view('raffles.index');
    }

    public function show(string $raffle_id): View
    {
        $raffle = Raffle::query()
            ->where('raffle_id', $raffle_id)
            ->firstOrFail();

        $tickets = $raffle->tickets()
            ->orderBy('position')
            ->paginate(100)
            ->withQueryString();

        return view('raffles.show', [
            'raffle' => $raffle,
            'tickets' => $tickets,
        ]);
    }
}
