<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GameMatch;

class MatchController extends Controller
{
    public function index()
    {
        $matches = GameMatch::all();

        return response()->json([
            'status' => 200,
            'data' => $matches
        ]);

    }

    public function matchesByUser($user_id)
    {
        return GameMatch::where('player1_user_id', $user_id)->get();
    }

    public function show($id)
    {
        $match = GameMatch::find($id);

        if (!$match) {
            return response()->json([
                'status' => 'error',
                'message' => 'Match não encontrado'
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'data' => $match
        ]);
    }

    public function create(Request $request){
        $request->validate([
            'player1_user_id' => 'required|exists:users,id',
            'type' => 'nullable|in:3,9',
            'stake' => 'nullable|integer|min:1'
        ]);

        $match = GameMatch::create([
            'player1_user_id' => $request->player1_user_id,
            'player2_user_id' => $request->player1_user_id, //singleplayer
            'type' => $request->type,
            'stake' => $request->stake ?? 3,
            'status' => 'Playing',
            'player1_marks' => 0,
            'player2_marks' => 0,
            'began_at' => now(),
        ]);

        return response()->json([
            'status' => 201,
            'message' => 'Partida criada com sucesso',
            'data' => $match
        ]);
    }

    public function unfinishedMatchesByUser($user_id){
        $matches = GameMatch::where('player1_user_id', $user_id)->where('status', 'Playing')->get();

        return response()->json([
            'status' => 200,
            'data' => $matches
        ]);
    }

    public function interruptGame($match_id){
        $match = GameMatch::find($match_id);

        if(!$match){
            return response()->json([
                'status' => 404,
                'message' => 'Match não encontrado'
            ]);
        }

        $match->status = 'Interrupted';
        $match->ended_at = now();
        $match->save();

        return response()->json([
            'status' => 200,
            'message' => 'Partida interrompida com sucesso',
            'data' => $match
        ]);
    }
}