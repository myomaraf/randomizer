<?php

use App\Http\Controllers\Api\RandomizeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['allowed.origin', 'throttle:randomize'])->group(function () {
    Route::post('/randomize', RandomizeController::class);
});
