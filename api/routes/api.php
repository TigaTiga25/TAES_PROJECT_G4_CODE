<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Auth\Events\Verified;

// --- IMPORTS DOS CONTROLLERS ---
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CoinTransactionController;
use App\Http\Controllers\ScoreboardController;
use App\Http\Controllers\NotificationController;

use App\Models\User;
use App\Models\Notification;

/*
|--------------------------------------------------------------------------
| Rotas de Admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/admin/announce-item', function (Request $request) {
        $request->validate([
            'name' => 'required|string',
            'type' => 'required|in:AVATAR,DECK'
        ]);

        $dbType = 'SHOP_' . $request->input('type');
        $itemName = $request->input('name');
        $itemType = $request->input('type');

        $users = User::all();
        foreach ($users as $user) {
            Notification::create([
                'user_id' => $user->id,
                'type'    => $dbType,
                'title'   => 'New Item in Store!',
                'message' => "A new {$itemName} is now available. Check the {$itemType} Shop!",
                'read'    => false
            ]);
        }
        return response()->json(['message' => "Anúncio enviado!", 'item' => $itemName]);
    });
});

/*
|--------------------------------------------------------------------------
| Rotas Públicas
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/matches', [MatchController::class, 'index']);
Route::get('/metadata', function () {
    return ["name" => "TAES Project API", "version" => "1.0.0"];
});
Route::post('/debug/notify', [NotificationController::class, 'createMock']);

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
| Rotas Protegidas
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // --- 1. AUTH & USER PROFILE ---
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Perfil
    Route::get('/users/me', [UserController::class, 'show']);
    Route::patch('/users/me', [UserController::class, 'update']);
    Route::patch('/users/me/deck', [UserController::class, 'updateDeck']);

    // --- 2. LOJA & MOEDAS  ---
    // Histórico
    Route::get('/transactions', [CoinTransactionController::class, 'index']);

    // Compras
    Route::post('/shop/buy-coins', [CoinTransactionController::class, 'buyCoins']);
    Route::post('/shop/buy-avatar', [CoinTransactionController::class, 'buyAvatar']);
    Route::post('/shop/buy-deck', [CoinTransactionController::class, 'buyDeck']);


    // --- 3. JOGO & MATCHES ---
    Route::post('/matches', [MatchController::class, 'create']);
    Route::put('/matches/{match_id}/finish', [MatchController::class, 'finishGame']);
    Route::put('/matches/{match_id}/interrupt', [MatchController::class, 'interruptGame']);

    Route::get('/matches/{user_id}/unfinished', [MatchController::class, 'unfinishedMatchesByUser']);
    Route::get('/matches/{user_id}/finished', [MatchController::class, 'finishedMatchesByUser']);
    Route::get('/games/{user_id}/finished', [GameController::class, 'finishedGamesByUser']);

    Route::post('/matches/{match_id}/game', [GameController::class, 'create']);
    Route::put('/games/{game_id}/finishGame', [GameController::class, 'finishGame']);


    // --- 4. ESTATÍSTICAS ---
    Route::get('/statistics/personal', [ScoreboardController::class, 'getPersonalStats']);
    Route::get('/statistics/global', [ScoreboardController::class, 'getGlobalRankings']);


    // --- 5. NOTIFICAÇÕES ---
    Route::get('/notifications/unread', [NotificationController::class, 'getUnread']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

});
