<?php

use App\Http\Controllers\RafflesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});


Route::middleware(['throttle:3,1'])->get('/run-migration', function (Request $request) {
    $expectedToken = (string) env('MAINTENANCE_ROUTE_TOKEN', '');
    $providedToken = (string) $request->query('token', '');

    if ($expectedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
        abort(404);
    }

    $output = [];

    try {
        Artisan::call('migrate', ['--force' => true]);
        $output[] = '[migrate] OK';
        $artisanOutput = trim(Artisan::output());
        if ($artisanOutput !== '') {
            $output[] = $artisanOutput;
        }
    } catch (\Throwable $e) {
        $output[] = '[migrate] ERROR: ' . $e->getMessage();
    }

    return response('<pre>' . e(implode("\n\n", $output)) . '</pre>');
});

Route::get('/raffles', [RafflesController::class, 'index'])->name('raffles.index');
Route::get('/raffles/{raffle_id}', [RafflesController::class, 'show'])->name('raffles.show');
Route::get('/raffles/{raffle_id}/tickets/data', [RafflesController::class, 'ticketsData'])->name('raffles.tickets.data');
Route::get('/raffles/{raffle_id}/tickets/export.csv', [RafflesController::class, 'exportTicketsCsv'])->name('raffles.tickets.export.csv');
Route::get('/raffles/{raffle_id}/tickets/export.xls', [RafflesController::class, 'exportTicketsExcel'])->name('raffles.tickets.export.xls');
