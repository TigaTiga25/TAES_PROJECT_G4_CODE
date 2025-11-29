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

        $totalMatches = GameMatch::where('status', 'Ended')
            ->where(function($query) use ($userId) {
            $query->where('player1_user_id', $userId)
                  ->orWhere('player2_user_id', $userId);
            })->count();

        // Vitórias em Partidas
        $winsMatches = GameMatch::where('status', 'Ended')
            ->where('winner_user_id', $userId)
            ->count();

        // Tempo Total (Apenas de jogos terminados)
        $totalTime = Game::where('status', 'Ended')
            ->where(function($query) use ($userId) {
                $query->where('player1_user_id', $userId)
                      ->orWhere('player2_user_id', $userId);
            })->sum('total_time');

        $totalGames = Game::where('status', 'Ended')
            ->where(function($query) use ($userId) {
                $query->where('player1_user_id', $userId)
                      ->orWhere('player2_user_id', $userId);
            })->count();

        $winsGames = Game::where('status', 'Ended')
            ->where('winner_user_id', $userId)
            ->count();


        // Pontos (Apenas de jogos terminados)
        $pointsP1 = Game::where('status', 'Ended')
            ->where('player1_user_id', $userId)
            ->sum('player1_points');

        $pointsP2 = Game::where('status', 'Ended')
            ->where('player2_user_id', $userId)
            ->where('player1_user_id', '!=', $userId) // Só soma se não for o próprio user (Bot)
            ->sum('player2_points');

        $totalPoints = $pointsP1 + $pointsP2;

        // --- ESTATÍSTICAS DETALHADAS (Tabela Games) ---

        $draws = Game::where('status', 'Ended')
            ->where('is_draw', 1)
            ->where(function($query) use ($userId) {
                $query->where('player1_user_id', $userId)
                      ->orWhere('player2_user_id', $userId);
            })->count();

        $riscas = Game::where('status', 'Ended')
            ->where(function($masterQuery) use ($userId) {
                $masterQuery->where(function ($query) use ($userId) {
                    $query->where('player1_user_id', $userId)->whereBetween('player1_points', [61, 89]);
                })->orWhere(function ($query) use ($userId) {
                    $query->where('player2_user_id', $userId)
                          ->where('player1_user_id', '!=', $userId)
                          ->whereBetween('player2_points', [61, 89]);
                });
            })->count();

        $capotes = Game::where('status', 'Ended')
            ->where(function($masterQuery) use ($userId) {
                $masterQuery->where(function ($query) use ($userId) {
                    $query->where('player1_user_id', $userId)->whereBetween('player1_points', [90, 119]);
                })->orWhere(function ($query) use ($userId) {
                    $query->where('player2_user_id', $userId)
                          ->where('player1_user_id', '!=', $userId)
                          ->whereBetween('player2_points', [90, 119]);
                });
            })->count();

        $bandeiras = Game::where('status', 'Ended')
            ->where(function($masterQuery) use ($userId) {
                $masterQuery->where(function ($query) use ($userId) {
                    $query->where('player1_user_id', $userId)->where('player1_points', 120);
                })->orWhere(function ($query) use ($userId) {
                    $query->where('player2_user_id', $userId)
                          ->where('player1_user_id', '!=', $userId)
                          ->where('player2_points', 120);
                });
            })->count();

        // --- RESPOSTA JSON ---
        return response()->json([
            'totalMatches' => $totalMatches,
            'winsMatches'  => $winsMatches,
            'totalTime'    => $totalTime,
            'totalGames'   => $totalGames,
            'winsGames'    => $winsGames,
            'totalPoints'  => $totalPoints,
            'draws'        => $draws,
            'riscas'       => $riscas,
            'capotes'      => $capotes,
            'bandeiras'    => $bandeiras,
        ]);
    }
}
