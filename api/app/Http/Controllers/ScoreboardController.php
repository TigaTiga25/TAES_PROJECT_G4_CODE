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

        $winsMatches = GameMatch::where('winner_user_id', $userId)->count();

        $totalGames = Game::where(function($query) use ($userId) {
            $query->where('player1_user_id', $userId)
                  ->orWhere('player2_user_id', $userId);
        })->count();

        $winsGames= Game::where('winner_user_id', $userId)->count();

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
            'totalGames'   => $totalGames,
            'winsGames'         => $winsGames,
            'draws'        => $draws,
            'riscas'       => $riscas,
            'capotes'      => $capotes,
            'bandeiras'    => $bandeiras,
        ]);
    }
}
