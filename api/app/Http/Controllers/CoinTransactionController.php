<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CoinTransaction;
use App\Models\CoinPurchase;
use App\Models\CoinTransactionType;

class CoinTransactionController extends Controller
{
    // --------------------------------------------------------------------
    // 1. HISTÓRICO DE TRANSAÇÕES
    // --------------------------------------------------------------------
    public function index(Request $request)
    {
        // Retorna as transações ordenadas (definido no User Model)
        // O 'with' traz o nome do tipo (ex: "Coin purchase")
        return $request->user()
            ->transactions()
            ->with('type')
            ->get();
    }

    // --------------------------------------------------------------------
    // 2. COMPRAR MOEDAS (EUROS -> MOEDAS)
    // --------------------------------------------------------------------
    public function buyCoins(Request $request)
    {
        // Validação dos dados que vêm do Frontend
        $request->validate([
            'euros' => 'required|numeric|min:1', // Valor em €
            'payment_type' => 'required|in:MBWAY,PAYPAL,VISA,MB',
            'payment_ref' => 'required|string' // Ex: 911222333 ou email
        ]);

        $user = $request->user();
        $euros = $request->euros;

        // TAXA DE CONVERSÃO: 1 Euro = 10 Moedas (Ajusta se quiseres)
        $coinsToAdd = (int)($euros * 10);

        // Transação Atómica: Garante que cria as duas linhas ou falha tudo
        DB::transaction(function () use ($user, $euros, $coinsToAdd, $request) {

            // A. Buscar o ID do tipo "Coin purchase" (ID 2 no Seeder)
            $type = CoinTransactionType::where('name', 'Coin purchase')->first();
            $typeId = $type ? $type->id : 2;

            // B. Criar o registo de Crédito de Moedas
            $transaction = CoinTransaction::create([
                'user_id' => $user->id,
                'coin_transaction_type_id' => $typeId,
                'transaction_datetime' => now(),
                'coins' => $coinsToAdd, // Valor Positivo
                'custom' => ['source' => 'store_purchase']
            ]);

            // C. Criar o registo da Compra em Dinheiro (Fatura)
            CoinPurchase::create([
                'user_id' => $user->id,
                'coin_transaction_id' => $transaction->id, // Liga à transação acima
                'purchase_datetime' => now(),
                'euros' => $euros,
                'payment_type' => $request->payment_type,
                'payment_reference' => $request->payment_ref,
                'custom' => ['obs' => 'Payment successful']
            ]);

            // D. Atualizar Saldo do User
            $user->coins_balance += $coinsToAdd;
            $user->save();
        });

        return response()->json([
            'message' => 'Compra realizada com sucesso!',
            'added_coins' => $coinsToAdd,
            'new_balance' => $user->coins_balance
        ]);
    }

    // --------------------------------------------------------------------
    // 3. COMPRAR AVATAR (LOJA)
    // --------------------------------------------------------------------
    public function buyAvatar(Request $request)
    {
        $request->validate([
            'avatar_id' => 'required|string', // ex: 'avatar_mario'
            'price' => 'required|integer|min:0'
        ]);

        $user = $request->user();
        $price = $request->price;
        $avatarId = $request->avatar_id;

        // A. Verificar Saldo
        if ($user->coins_balance < $price) {
            return response()->json(['message' => 'Saldo insuficiente.'], 422);
        }

        // B. Verificar Inventário (unlocked_avatars)
        $myAvatars = $user->unlocked_avatars ?? [];

        // Avatares gratuitos (para não deixar comprar o que já é grátis)
        $defaultAvatars = ['Felix', 'Aneka', 'Zack', 'Midnight', 'Bear'];

        if (in_array($avatarId, $myAvatars) || in_array($avatarId, $defaultAvatars)) {
            return response()->json(['message' => 'Já tens este avatar!'], 409);
        }

        // C. Executar Compra
        DB::transaction(function () use ($user, $price, $avatarId, $myAvatars) {

            // 1. Atualizar User
            $user->coins_balance -= $price;
            $myAvatars[] = $avatarId;
            $user->unlocked_avatars = $myAvatars; // O Cast do Model converte para JSON
            $user->save();

            // 2. Buscar Tipo "Avatar purchase" (ID 7)
            $type = CoinTransactionType::where('name', 'Avatar purchase')->first();
            $typeId = $type ? $type->id : 7; // Fallback para 7 se não achar

            // 3. Criar Transação (Débito)
            CoinTransaction::create([
                'user_id' => $user->id,
                'coin_transaction_type_id' => $typeId,
                'transaction_datetime' => now(),
                'coins' => -$price, // Valor Negativo
                'custom' => [
                    'item_type' => 'avatar',
                    'item_id' => $avatarId
                ]
            ]);
        });

        return response()->json([
            'message' => 'Avatar comprado!',
            'new_balance' => $user->coins_balance,
            'wallet' => $user->unlocked_avatars
        ]);
    }

    // --------------------------------------------------------------------
    // 4. COMPRAR BARALHO (LOJA)
    // --------------------------------------------------------------------
    public function buyDeck(Request $request)
    {
        $request->validate([
            'deck_id' => 'required|string', // ex: 'deck_matrix'
            'price' => 'required|integer|min:0'
        ]);

        $user = $request->user();
        $price = $request->price;
        $deckId = $request->deck_id;

        // A. Verificar Saldo
        if ($user->coins_balance < $price) {
            return response()->json(['message' => 'Saldo insuficiente.'], 422);
        }

        // B. Verificar Inventário
        $myDecks = $user->unlocked_decks ?? [];
        // O 'default' toda a gente tem
        if (in_array($deckId, $myDecks) || $deckId === 'default') {
            return response()->json(['message' => 'Já tens este baralho!'], 409);
        }

        // C. Executar Compra
        DB::transaction(function () use ($user, $price, $deckId, $myDecks) {

            // 1. Atualizar User
            $user->coins_balance -= $price;
            $myDecks[] = $deckId;
            $user->unlocked_decks = $myDecks;
            $user->save();

            // 2. Buscar Tipo "Deck purchase" (ID 8)
            $type = CoinTransactionType::where('name', 'Deck purchase')->first();
            $typeId = $type ? $type->id : 8;

            // 3. Criar Transação
            CoinTransaction::create([
                'user_id' => $user->id,
                'coin_transaction_type_id' => $typeId,
                'transaction_datetime' => now(),
                'coins' => -$price,
                'custom' => [
                    'item_type' => 'deck',
                    'item_id' => $deckId
                ]
            ]);
        });

        return response()->json([
            'message' => 'Baralho comprado!',
            'new_balance' => $user->coins_balance,
            'wallet' => $user->unlocked_decks
        ]);
    }
}
