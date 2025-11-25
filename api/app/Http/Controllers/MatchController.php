<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GameMatch;
use App\Models\User;
use App\Models\CoinTransaction;
use Illuminate\Support\Facades\DB;

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

    // ------------------------------------------------------------------------
    // CRIAR PARTIDA (Cobra a Aposta)
    // ------------------------------------------------------------------------
    public function create(Request $request){
        
        $request->validate([
            'player1_user_id' => 'required|exists:users,id',
            'type' => 'nullable|in:3,9',
            'stake' => 'nullable|integer|min:3' 
        ]);

        $user = User::find($request->player1_user_id);

        if($user->coins_balance < 3){ //entry fee
            return response()->json([
                'status' => 400,
                'message' => 'insufficient balance'
            ]);
        }

        $user->coins_balance -= 3;
        $user->save();

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
            'message' => 'Partida criada e aposta paga com sucesso',
            'data' => $match,
            'new_balance' => $user->coins_balance
        ]);
    }

    // ------------------------------------------------------------------------
    // TERMINAR PARTIDA (Paga o Prémio)
    // ------------------------------------------------------------------------
    public function finishGame(Request $request, $match_id)
    {
        // O frontend deve dizer quem ganhou (ID do user)
        $request->validate(['winner_id' => 'required|exists:users,id']);

        $match = GameMatch::find($match_id);

        if(!$match || $match->status !== 'Playing') {
            return response()->json(['message' => 'Jogo não encontrado ou já terminado'], 400);
        }

        DB::transaction(function () use ($match, $request) {
            // 1. Atualizar o estado da partida
            $match->status = 'Ended';
            $match->winner_user_id = $request->winner_id;
            $match->ended_at = now();
            $match->save();

            // 2. Calcular o Prémio conforme regras do enunciado 
            // "Winner receives combined stake... minus 1 coin commission"
            $totalPot = $match->stake * 2; // Aposta do Jogador + Aposta do Bot
            $prize = $totalPot - 1; // Comissão da plataforma

            // 3. Pagar ao Vencedor
            $winner = User::find($request->winner_id);
            $winner->coins_balance += $prize;
            $winner->save();

            // 4. Registar o prémio no histórico (ID 6 = Match Payout)
            CoinTransaction::create([
                'user_id' => $winner->id,
                'coin_transaction_type_id' => 6, // ID 6: Prémio de Vitória
                'coin_amount' => $prize,
            ]);
        });

        return response()->json(['status' => 200, 'message' => 'Partida terminada e prémio entregue.']);
    }

    public function unfinishedMatchesByUser($user_id){
        $matches = GameMatch::where('player1_user_id', $user_id)->where('status', 'Playing')->orderBy('began_at', 'desc')->get();

        return response()->json([
            'status' => 200,
            'data' => $matches
        ]);
    }

    public function finishedMatchesByUser($user_id){
        $matches = GameMatch::where('matches.player1_user_id', $user_id)
                    ->where('matches.status', 'Ended')
                    ->leftJoin('users as u1', 'u1.id', '=', 'matches.player1_user_id')
                    ->leftJoin('users as u2', 'u2.id', '=', 'matches.player2_user_id')
                    ->select(
                        'matches.*',
                        'u1.name as player1_name',
                        'u2.name as player2_name'
                    )
                    ->get();

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