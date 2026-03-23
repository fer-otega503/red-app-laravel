<?php

use App\http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('users', UserController::class)
    ->only(['index','store', 'show', 'update', 'destroy'])
    ->names('api.user');
