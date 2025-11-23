<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return ['user' => $request->user()];
})->middleware('auth:sanctum');

