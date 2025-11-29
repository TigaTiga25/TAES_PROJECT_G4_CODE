<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Support\Facades\Hash;    

class UserController extends Controller
{
    // --------------------------------------------------------------------
    // OBTER TRANSAÇÕES
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
            $coinsToAdd = $euros * 10; 

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

    // --------------------------------------------------------------------
    // ATUALIZAR PERFIL (FOTO + NOME + PASS)
    // --------------------------------------------------------------------
    public function update(Request $request, User $user)
    {
        // 1. Validar tudo (nome, password e ficheiro)
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'nullable|string|min:3',
            'file' => 'nullable|image|max:4096', // Valida se é imagem e tamanho máx 4MB
        ]);

        // 2. Atualizar Nome
        $user->name = $validated['name'];

        // 3. Atualizar Password (apenas se foi enviada)
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        // 4. Lógica da Imagem
        if ($request->hasFile('file')) {
            
            // A. Apagar a foto antiga se existir 
            if ($user->photo_avatar_filename && Storage::disk('public')->exists('photos_avatars/' . $user->photo_avatar_filename)) {
                Storage::disk('public')->delete('photos_avatars/' . $user->photo_avatar_filename);
            }

            // B. Guardar a nova foto na pasta 'storage/app/public/photos_avatars'
            $path = $request->file('file')->store('photos_avatars', 'public');

            // C. Guardar apenas o nome do ficheiro na BD (ex: 'hash.jpg')
            $user->photo_avatar_filename = basename($path);
        }

        $user->save();

        // Retornar o user atualizado para o Vue atualizar a store
        return response()->json([
            'message' => 'Profile updated successfully!',
            'data' => $user
        ]);
    }
}