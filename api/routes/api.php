<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ScoreboardController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\NotificationController;

/*
|--------------------------------------------------------------------------
| Rotas Públicas (Não requerem login)
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Lobby: Listar jogos disponíveis (Pode ser público para visitantes verem)
Route::get('/matches', [MatchController::class, 'index']);

Route::get('/metadata', function (Request $request) {
    return [
        "name" => "TAES 2025/26 Project API",
        "version" => "0.0.1"
    ];
});

// Verificação de Email
Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
    $user = User::findOrFail($id);
    if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
        abort(403);
    }
    if (! $user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
        event(new Verified($user));
    }
    return redirect('http://localhost:5173/?verified=true');
})->middleware(['signed'])->name('verification.verify');


/*
|--------------------------------------------------------------------------
| Rotas Protegidas (Requerem Token Bearer)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // --- AUTH & USER ---
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) { return $request->user(); });

    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::get('/users/transactions', [UserController::class, 'getTransactions']);
    Route::post('/users/buy-coins', [UserController::class, 'buyCoins']);

    // --- SCOREBOARD / ESTATÍSTICAS ---
    Route::get('/statistics/personal', [ScoreboardController::class, 'getPersonalStats']);
    Route::get('/statistics/global', [ScoreboardController::class, 'getGlobalRankings']);

    // --- MATCHES (Gestão de Partidas) ---
    Route::post('/matches', [MatchController::class, 'create']);
    Route::put('/matches/{match_id}/finish', [MatchController::class, 'finishGame']); // Terminar a Partida inteira
    Route::put('/matches/{match_id}/interrupt', [MatchController::class, 'interruptGame']);

    // Histórico de Jogos do Utilizador
    Route::get('/matches/{user_id}/unfinished', [MatchController::class, 'unfinishedMatchesByUser']);
    Route::get('/matches/{user_id}/finished', [MatchController::class, 'finishedMatchesByUser']);
    Route::get('/games/{user_id}/finished', [GameController::class, 'finishedGamesByUser']);

    // --- GAMES (Jogos individuais dentro da partida) ---
    Route::post('/matches/{match_id}/game', [GameController::class, 'create']);
    Route::put('/games/{game_id}/finishGame', [GameController::class, 'finishGame']); // Terminar um jogo parcial (mão)

    // --- TRANSACTIONS (Transações de Moedas) ---
    Route::post('/transactions', [TransactionController::class, 'store']);

    Route::post('/avatars/buy', [App\Http\Controllers\UserController::class, 'buyAvatar']);

    Route::post('/users/buy-deck', [UserController::class, 'buyDeck']);
    Route::patch('/users/update-deck', [UserController::class, 'updateDeck']);

    Route::get('/notifications/unread', [NotificationController::class, 'getUnread']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    // Debug
    Route::post('/debug/notify', [NotificationController::class, 'createMock']);

});
