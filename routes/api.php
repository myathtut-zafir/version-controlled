<?php

use App\Http\Controllers\Api\V1\ObjectController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/objects')->group(function () {
    Route::get('/', [ObjectController::class, 'index']);
    Route::post('/', [ObjectController::class, 'store']);
    Route::get('/{key}', [ObjectController::class, 'show']);
    Route::get('/keys/{key}', [ObjectController::class, 'getValueAtTimestamp']);
});
