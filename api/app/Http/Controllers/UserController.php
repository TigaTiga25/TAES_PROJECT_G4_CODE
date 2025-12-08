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
        $transactions = $request->user()
            ->transactions()
            ->orderBy('date', 'desc')
            ->get();

        return response()->json(['data' => $transactions]);
    }

    // --------------------------------------------------------------------
    // COMPRAR MOEDAS 
    // --------------------------------------------------------------------
    public function buyCoins(Request $request)
    {
        $request->validate([
            'cost' => 'required|integer|min:1',
            'payment_type' => 'required|string'
        ]);

        $euros = $request->cost;
        $coinsToAdd = $euros * 10; 
        $user = $request->user();

        // 1. Criar Transação
        $user->transactions()->create([
            'type' => 'I',
            'value' => $coinsToAdd,
            'description' => 'Bought ' . $coinsToAdd . ' coins',
            'date' => now(),
        ]);

        // 2. Atualizar Saldo
        $user->coins_balance += $coinsToAdd;
        $user->save();

        return response()->json([
            'message' => "Compra realizada!",
            'new_balance' => $user->coins_balance
        ]);
    }

    // --------------------------------------------------------------------
    // COMPRAR AVATAR (LÓGICA SEGURA E PERMANENTE)
    // --------------------------------------------------------------------
    public function buyAvatar(Request $request)
    {
        $request->validate([
            'seed' => 'required|string',
            'price' => 'required|integer|min:0'
        ]);

        $user = $request->user();

        // Lista de avatares padrão
        $defaultAvatars = ['Felix', 'Aneka', 'Zack', 'Midnight', 'Bear'];

        // Obtém os avatares que o user já tem na BD
        $myAvatars = $user->unlocked_avatars ?? [];

        // Se estiver vazia ou null, assume os defaults
        if (empty($myAvatars)) {
            $myAvatars = $defaultAvatars;
        }

        // 1. Verificar se já tem o avatar
        if (in_array($request->seed, $myAvatars)) {
            return response()->json(['message' => 'Já tens este avatar!'], 422);
        }

        // 2. Verificar Saldo
        if ($user->coins_balance < $request->price) {
            return response()->json(['message' => 'Saldo insuficiente.'], 422);
        }

        // 3. Registar a Transação
        $user->transactions()->create([
            'type' => 'P',
            'value' => -$request->price,
            'description' => 'Bought avatar: ' . $request->seed,
            'date' => now(),
        ]);

        // 4. Atualizar User (Saldo e Array)
        $user->coins_balance -= $request->price;
        
        $myAvatars[] = $request->seed; // Adiciona o novo à lista PHP
        $user->unlocked_avatars = $myAvatars; // Guarda a lista atualizada no Model
        
        $user->save(); 

        return response()->json([
            'message' => 'Avatar comprado com sucesso!',
            'user' => $user 
        ]);
    }

    // --------------------------------------------------------------------
    // ATUALIZAR PERFIL
    // --------------------------------------------------------------------
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'nickname' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:3',
            'file' => 'nullable|image|max:4096',
            'custom_avatar_seed' => 'sometimes|string',
            'unlocked_avatars' => 'sometimes|array'
        ]);

        // Atualizar Campos Simples
        if ($request->has('nickname')) {
            $user->nickname = $validated['nickname'];
        }

        if ($request->has('custom_avatar_seed')) {
            $user->custom_avatar_seed = $request->input('custom_avatar_seed');
        }

   
        if ($request->has('unlocked_avatars')) {
            $user->unlocked_avatars = $request->input('unlocked_avatars');
        }
        // --------------------------------

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        // Atualizar Foto
        if ($request->hasFile('file')) {
            if ($user->photo_avatar_filename && Storage::disk('public')->exists('photos_avatars/' . $user->photo_avatar_filename)) {
                Storage::disk('public')->delete('photos_avatars/' . $user->photo_avatar_filename);
            }
            $path = $request->file('file')->store('photos_avatars', 'public');
            $user->photo_avatar_filename = basename($path);
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully!',
            'data' => $user
        ]);
    }
    // --------------------------------------------------------------------
    // COMPRAR BARALHO (DECK)
    // --------------------------------------------------------------------
    public function buyDeck(Request $request)
    {
        $request->validate([
            'deck' => 'required|string', // Nome do baralho (ex: 'retro')
            'price' => 'required|integer|min:0'
        ]);

        $user = $request->user();
        
        // Garante que a lista não é null
        $myDecks = $user->unlocked_decks ?? [];
        
        // O baralho 'default' todos têm, se a lista tiver vazia, adiciona-o
        if (empty($myDecks)) {
            $myDecks[] = 'default';
        }

        // 1. Verificar se já tem o baralho
        if (in_array($request->deck, $myDecks)) {
            return response()->json(['message' => 'Já tens este baralho!'], 422);
        }

        // 2. Verificar Saldo
        if ($user->coins_balance < $request->price) {
            return response()->json(['message' => 'Saldo insuficiente.'], 422);
        }

        // 3. Registar a Transação
        $user->transactions()->create([
            'type' => 'P', // Purchase
            'value' => -$request->price,
            'description' => 'Bought deck: ' . $request->deck,
            'date' => now(),
        ]);

        // 4. Atualizar User (Saldo e Lista de Decks)
        $user->coins_balance -= $request->price;
        
        $myDecks[] = $request->deck; // Adiciona o novo
        $user->unlocked_decks = $myDecks; // Guarda
        
        $user->save(); 

        return response()->json([
            'message' => 'Baralho comprado com sucesso!',
            'user' => $user 
        ]);
    }

    // --------------------------------------------------------------------
    // EQUIPAR BARALHO (CHANGE DECK)
    // --------------------------------------------------------------------
    public function updateDeck(Request $request)
    {
        $request->validate([
            'deck' => 'required|string',
        ]);

        $user = $request->user();
        $myDecks = $user->unlocked_decks ?? ['default'];

        // Só pode equipar se tiver o baralho na lista
        if (!in_array($request->deck, $myDecks) && $request->deck !== 'default') {
            return response()->json(['message' => 'Não possuis este baralho.'], 403);
        }

        $user->current_deck = $request->deck;
        $user->save();

        return response()->json([
            'message' => 'Baralho equipado!',
            'current_deck' => $user->current_deck,
            'user' => $user
        ]);
    }
}