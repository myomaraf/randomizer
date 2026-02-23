<?php

use App\Http\Controllers\RafflesController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::get('/raffles', [RafflesController::class, 'index'])->name('raffles.index');
Route::get('/raffles/{raffle_id}', [RafflesController::class, 'show'])->name('raffles.show');
