<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MatchController; 
use App\Http\Controllers\GameController; 

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

//TODO: Adicionar o middleware 
Route::get('/matches', [MatchController::class, 'index']); //debug
Route::post('/matches', [MatchController::class, 'create']); 
Route::post('/matches/{match_id}/game', [GameController::class, 'create']);
Route::put('/games/{game_id}/finishGame', [GameController::class, 'finishGame']);
Route::get('/matches/{user_id}/unfinished', [MatchController::class, 'unfinishedMatchesByUser']);
Route::put('/matches/{match_id}/interrupt', [MatchController::class, 'interruptGame']); //ignorar


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


