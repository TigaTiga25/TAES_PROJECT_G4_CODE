<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


class TransactionController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validar os dados
        $validated = $request->validate([ 
            'value' => 'required|integer', 
            'description' => 'nullable|string'
        ]);

        $user = $request->user();

        // 2. Verificar saldo (se for compra)
        if ($validated['value'] < 0) {
            if ($user->coins_balance < abs($validated['value'])) {
                return response()->json(['message' => 'Saldo insuficiente.'], 422);
            }
        }

        // 3. Criar Transação
        // Certifica-te que tens a relação transactions() no model User
        // Caso contrário usa: Transaction::create([... 'user_id' => $user->id ...]);
        $transaction = $user->transactions()->create([
            'value' => $validated['value'],
            'description' => $validated['description'] ?? 'Movement',
            'date' => now(), 
        ]);

        // 4. Atualizar Saldo
        $user->coins_balance += $validated['value'];
        $user->save();

        return response()->json([
            'message' => 'Transaction successful',
            'data' => $transaction,
            'user' => $user
        ]);
    }
}