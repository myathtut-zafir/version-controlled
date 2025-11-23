<?php

use App\Http\Controllers\Api\V1\ObjectStoreController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/object', [ObjectStoreController::class, 'store']);
});
