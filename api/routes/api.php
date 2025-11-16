<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController; 

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);


Route::get('/metadata', function (Request $request) {
    return [
        "name" => "TAES 25025/26 Project API",
        "version" => "0.0.1"
    ];
});


// para que só funcione se o utilizador tiver feito login
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});