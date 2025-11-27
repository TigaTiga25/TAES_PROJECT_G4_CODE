<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GameMatch;
use App\Models\Game;
use Illuminate\Support\Facades\Auth;

class ScoreboardController extends Controller
{
    public function getPersonalStats(Request $request)
    {
        // 1. Obter o ID do utilizador autenticado
        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['error' => 'Utilizador não autenticado'], 401);
        }
        // --- ESTATÍSTICAS GERAIS ---

        $totalMatches = GameMatch::where(function($query) use ($userId) {
            $query->where('player1_user_id', $userId)
                  ->orWhere('player2_user_id', $userId);
        })->count();

        // vitórias contra outros jogadores (winner_user_id definido e players diferentes)
        $humanWins = GameMatch::where('winner_user_id', $userId)->count();

        // vitórias contra bot (és sempre player1)
        $botWins = GameMatch::where('player1_user_id', $userId)
            ->where('player2_user_id', $userId)
            ->whereColumn('player1_marks', '>', 'player2_marks')
            ->count();

        $winsMatches = $humanWins + $botWins;


        $totalTime = GameMatch::where(function($query) use ($userId) {
            $query->where('player1_user_id', $userId)
                  ->orWhere('player2_user_id', $userId);
        })->sum('total_time');

        $totalGames = Game::where(function($query) use ($userId) {
            $query->where('player1_user_id', $userId)
                  ->orWhere('player2_user_id', $userId);
        })->count();

        $winsGames = Game::where(function($query) use ($userId) {
            $query->where('winner_user_id', $userId)
                  ->whereColumn('player1_user_id', '!=', 'player2_user_id');
        })->orWhere(function($query) use ($userId) {
            $query->where('player1_user_id', $userId)
                  ->where('player2_user_id', $userId)
                  ->whereColumn('player1_points', '>', 'player2_points');
        })->count();

        $pointsP1 = Game::where('player1_user_id', $userId)->sum('player1_points');


        $pointsP2 = Game::where('player2_user_id', $userId)
                        ->where('player1_user_id', '!=', $userId)
                        ->sum('player2_points');

        $totalPoints = $pointsP1 + $pointsP2;

        // --- ESTATÍSTICAS DETALHADAS (Tabela Games) ---

        $draws = Game::where(function ($query) use ($userId) {
            $query->where('player1_user_id', $userId)
                  ->where('player1_points', 60);
        })->orWhere(function ($query) use ($userId) {
            $query->where('player2_user_id', $userId)
                  ->where('player2_points', 60);
        })->count();

        $riscas = Game::where(function ($query) use ($userId) {
            $query->where('player1_user_id', $userId)
                ->whereBetween('player1_points', [61, 89]);
        })->orWhere(function ($query) use ($userId) {
            $query->where('player2_user_id', $userId)
                ->whereBetween('player2_points', [61, 89]);
        })->count();

        // --- ACHIEVEMENTS ---
        $capotes = Game::where(function ($query) use ($userId) {
            $query->where('player1_user_id', $userId)
                ->whereBetween('player1_points', [90, 119]);
        })->orWhere(function ($query) use ($userId) {
            $query->where('player2_user_id', $userId)
                ->whereBetween('player2_points', [90, 119]);
        })->count();

        $bandeiras = Game::where(function ($query) use ($userId) {
            $query->where('player1_user_id', $userId)
                ->where('player1_points', 120);
        })->orWhere(function ($query) use ($userId) {
            $query->where('player2_user_id', $userId)
                ->where('player2_points', 120);
        })->count();

        // --- RESPOSTA JSON ---
        return response()->json([
            'totalMatches' => $totalMatches,
            'winsmatches'         => $winsMatches,
            'totalTime'    => $totalTime,
            'totalGames'   => $totalGames,
            'winsGames'         => $winsGames,
            'totalPoints'  => $totalPoints,
            'draws'        => $draws,
            'riscas'       => $riscas,
            'capotes'      => $capotes,
            'bandeiras'    => $bandeiras,
        ]);
    }
}
