<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    // --------------------------------------------------------------------
    // OBTER TRANSAÇÕES (Corrigido para 'coins' e 'transaction_datetime')
    // --------------------------------------------------------------------
    public function getTransactions(Request $request)
    {
        try {
            $user = $request->user();

            $transactions = DB::table('coin_transactions')
                ->join('coin_transaction_types', 'coin_transactions.coin_transaction_type_id', '=', 'coin_transaction_types.id')
                ->where('coin_transactions.user_id', $user->id)
                ->select(
                    'coin_transactions.id',
                    'coin_transactions.coins as amount', 
                    'coin_transactions.transaction_datetime as date', 
                    'coin_transaction_types.name as type_name'
                )
                ->orderBy('coin_transactions.transaction_datetime', 'desc')
                ->get();

            return response()->json(['data' => $transactions]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro SQL: ' . $e->getMessage()], 500);
        }
    }

    // --------------------------------------------------------------------
    // COMPRAR MOEDAS
    // --------------------------------------------------------------------
    public function buyCoins(Request $request)
    {
        try {
            $request->validate([
                'cost' => 'required|integer|min:1',
                'payment_type' => 'required|string'
            ]);

            $euros = $request->cost;
            $coinsToAdd = $euros * 10; // 1€ = 10 Moedas

            $user = $request->user();

            DB::transaction(function () use ($user, $coinsToAdd) {
                
                // 1. Atualizar Saldo do User
                DB::table('users')
                    ->where('id', $user->id)
                    ->increment('coins_balance', $coinsToAdd);

                // 2. Registar Transação
                DB::table('coin_transactions')->insert([
                    'user_id' => $user->id,
                    'coin_transaction_type_id' => 2,
                    'coins' => $coinsToAdd, 
                    'transaction_datetime' => now(), 
                  
                ]);
            });

            $newBalance = DB::table('users')->where('id', $user->id)->value('coins_balance');

            return response()->json([
                'message' => "Compra realizada!",
                'new_balance' => $newBalance
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro na compra: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, User $user)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $user->update($request->only('name'));
        return response()->json(['message' => 'Perfil atualizado!']);
    }
}