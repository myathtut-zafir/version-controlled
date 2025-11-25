<?php

use App\Http\Controllers\Api\V1\ObjectController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/object', [ObjectController::class, 'index']);
    Route::post('/object', [ObjectController::class, 'store']);
    Route::get('/object/{key}', [ObjectController::class, 'show']);
    Route::get('/object/keys/{key}', [ObjectController::class, 'getValueAtTimestamp']);
});
