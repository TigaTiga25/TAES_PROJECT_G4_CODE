<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\GameController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use App\Http\Controllers\UserController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
Route::middleware('auth:sanctum')->get('/auth/me', [AuthController::class, 'me']);
Route::put('/users/{user}', [UserController::class, 'update']);

Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
    // Procura o utilizador pelo ID (se não existir, dá erro 404)
    $user = User::findOrFail($id);

    // Verifica se o hash do email bate certo (segurança)
    if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
        abort(403);
    }

    // Se o email ainda não estiver verificado...
    if (! $user->hasVerifiedEmail()) {
        // ...marca como verificado...
        $user->markEmailAsVerified();
        // ...e dispara o evento de confirmação
        event(new Verified($user));
    }

    // 3. Redireciona para o Frontend (ajusta a porta 5173 se necessário)
    // O parametro ?verified=true permite mostrares uma mensagem de sucesso no login se quiseres
    return redirect('http://localhost:5173/?verified=true');
})->middleware(['signed'])->name('verification.verify');

//TODO: Adicionar o middleware
Route::get('/matches', [MatchController::class, 'index']); //debug
Route::post('/matches', [MatchController::class, 'create']);
Route::post('/matches/{match_id}/game', [GameController::class, 'create']);
Route::put('/games/{game_id}/finishGame', [GameController::class, 'finishGame']);
Route::get('/matches/{user_id}/unfinished', [MatchController::class, 'unfinishedMatchesByUser']);
Route::get('/matches/{user_id}/finished', [MatchController::class, 'finishedMatchesByUser']);
Route::put('/matches/{match_id}/interrupt', [MatchController::class, 'interruptGame']); 


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


