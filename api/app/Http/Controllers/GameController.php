<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GameMatch;
use App\Models\Game;
use Carbon\Carbon;
use App\Models\User;
use App\Models\CoinTransaction;
use Illuminate\Support\Facades\DB;


class GameController extends Controller
{
    public function create($match_id)
    {
        $match = GameMatch::find($match_id);

        if($match->status !== 'Playing') {
            return response()->json(['message' => 'Esta partida não está ativa.'], 400);
        }

        $game = Game::create([
            'match_id'        => $match->id,
            'player1_user_id' => $match->player1_user_id,
            'player2_user_id' => $match->player1_user_id,
            'type'            => $match->type,
            'status'          => 'Playing',
            'began_at'        => now(),
            'player1_points'  => 0,
            'player2_points'  => 0
        ]);

        return response()->json([
            'status' => 201,
            'message' => 'Jogo criado com sucesso',
            'data' => $game
        ]);
    }

    public function finishGame(Request $request, $game_id)
    {
        $request->validate([
            'player1_points' => 'required|integer|min:0|max:120'
        ]);


        return DB::transaction(function () use ($request, $game_id) {
            $game = Game::findOrFail($game_id);

            if ($game->status !== 'Playing') {
                return response()->json(['message' => 'Este jogo já foi finalizado'], 400);
            }

            $match = GameMatch::lockForUpdate()->findOrFail($game->match_id);

            $p1Points = (int) $request->player1_points;
            $p2Points = 120 - $p1Points;

            $game->ended_at = now();
            $game->total_time = (int) Carbon::parse($game->began_at)->diffInSeconds(now());
            $game->player1_points = $p1Points;
            $game->player2_points = $p2Points;
            $game->status = 'Ended';

            if ($p1Points > $p2Points) {
                $game->winner_user_id = $game->player1_user_id;
                $game->loser_user_id = null; // Bot perdeu
            } elseif ($p2Points > $p1Points) {
                $game->winner_user_id = null; // Bot ganhou
                $game->loser_user_id = $game->player1_user_id;
            } else {
                // Empate (60-60)
                $game->winner_user_id = null;
                $game->loser_user_id = null;
                $game->is_draw = 1;
            }

            $game->save();

            if ($p1Points == 120) {
                $match->player1_marks += 4;
            } elseif ($p1Points > 90) {
                $match->player1_marks += 2;
            } elseif ($p1Points > 60) {
                $match->player1_marks += 1;
            } elseif ($p1Points == 60) {
                // Empate - Ninguém ganha marcas
            } elseif ($p1Points >= 30) {
                $match->player2_marks += 1;
            } elseif ($p1Points > 0) {
                $match->player2_marks += 2;
            } else {
                $match->player2_marks += 4;
            }

            //Verificar se a partida terminou
            $games = Game::where('match_id', $match->id)->where('status', 'Ended')->get();
            $coinsEarned = 0;

            if ($match->player1_marks >= 4 || $match->player2_marks >= 4) {
                $match->status = 'Ended';
                $match->ended_at = now();
                $match->total_time = $games->sum('total_time');
                $match->player1_points = $games->sum('player1_points');
                $match->player2_points = $games->sum('player2_points');

                $match->save();

                //calcular coins ganhas -> vitoria = 5 coins; por cada capote acresce 3 coins; se for bandeira ganha 20 coins
                if($match->player1_marks >= 4){
                    //Ganhou o jogador
                    $coinsEarned = 5;
                    foreach($games as $game){
                        if($game->player1_points == 120){
                            $coinsEarned = 20;
                            break;
                        }

                        if($game->player1_points > 90 && $game->player1_points < 120){
                            $coinsEarned += 3;
                        }
                    }

                    $user = User::find($match->player1_user_id);
                    $user->coins_balance += $coinsEarned;
                    $user->save();
                    CoinTransaction::create([
                        'transaction_datetime' => now(),
                        'match_id' => $match->id,
                        'user_id' => $user->id,
                        'coin_transaction_type_id' => 6,
                        'coins' => $coinsEarned,
                    ]);
                }

                return response()->json([
                    'status' => 201,
                    'message' => 'Jogo e partida finalizados com sucesso',
                    'match' => $match,
                    'games' => $games,
                    'coinsEarned' => $coinsEarned?$coinsEarned:0
                ]);
            }


            $match->save();

            return response()->json([
                'status' => 200,
                'message' => 'Jogo finalizado com sucesso',
                'data' => $game,
                'gameNumber' => $games->count(),
                'match' => $match
            ]);
        });
    }

    public function finishedGamesByUser($user_id){
        $matches = Game::where('player1_user_id', $user_id)->where('status', 'Ended')->orderBy('began_at', 'desc')->get();

        return response()->json([
            'status' => 200,
            'data' => $matches
        ]);
    }
}
