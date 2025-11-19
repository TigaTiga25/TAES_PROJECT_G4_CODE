<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GameMatch;
use App\Models\Game;
use Carbon\Carbon;

class GameController extends Controller
{
    public function create($match_id)
    {
        $match = GameMatch::find($match_id);


        $game = Game::create([
            'match_id'        => $match->id,
            'player1_user_id' => $match->player1_user_id,
            'player2_user_id' => $match->player1_user_id, //singleplayer
            'type'            => $match->type,
            'status'          => 'Playing',
            'began_at'        => now(),
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
            'player1_points' => 'required|integer|min:0'
        ]);

        $game = Game::find($game_id);
        $match = GameMatch::find($game->match_id);

        $game->ended_at = now();
        $game->total_time = (int) Carbon::parse($game->began_at)->diffInSeconds(now());
        $game->player1_points = $request->player1_points;
        $game->player2_points = 120 - $request->player1_points;
        $game->status = 'Ended';

        $game->save();

        switch($request->player1_points){
            case 120: 
                $match->player1_marks += 4;
                break;
            case 0:
                $match->player2_marks += 4;
                break;
            case 60:
                $match->player1_marks += 1;
                $match->player2_marks += 1;
                break;
            case ($request->player1_points > 90 && $request->player1_points < 120):
                $match->player1_marks += 2;
                break;
            case ($request->player1_points > 0 && $request->player1_points < 30):
                $match->player2_marks += 2;
                break;
            case ($request->player1_points > 60 && $request->player1_points < 91):
                $match->player1_marks += 1;
                break;
            default:
                $match->player2_marks += 1;
                break;
        }

        //Verificar se a partida terminou
        $games = Game::where('match_id', $match->id)->where('status', 'Ended')->get();

        
        if ($match->player1_marks >= 4 || $match->player2_marks >= 4) {
            $match->status = 'Ended';
            $match->ended_at = now();
            $match->total_time = $games->sum('total_time');
            $match->player1_points = $games->sum('player1_points');

            $match->save();

            return response()->json([
                'status' => 201,
                'message' => 'Jogo e partida finalizados com sucesso',
                'match' => $match,
                'games' => $games
            ]);
        }

        
        $match->save();

        return response()->json([
            'status' => 200,
            'message' => 'Jogo finalizado com sucesso',
            'data' => $game,
            'gameNumber' => $games->count()
        ]);
    }
}
