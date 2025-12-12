<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Registered;
use App\Models\CoinTransaction;

class AuthController extends Controller
{
    // ---------------------------
    // REGISTAR
    // ---------------------------
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        // 1. Criar o Utilizador
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => $validatedData['password'],
            'coins_balance' => 10, // Saldo Inicial

            'custom_avatar_seed' => 'Felix',
            'unlocked_avatars' => ['Felix', 'Aneka', 'Zack', 'Midnight', 'Bear'],
            'current_deck' => 'default',
            'unlocked_decks' => ['default'],
        ]);

        // 2. REGISTAR A TRANSAÇÃO NO HISTÓRICO
        CoinTransaction::create([
            'user_id' => $user->id,
            'coin_transaction_type_id' => 1, // Tipo 1 = Bónus Inicial
            'coins' => 10,
            'transaction_datetime' => now()
        ]);

        // 3. Dispara o evento de Registo
        event(new Registered($user));

        return response()->json([
            'message' => 'Registo efetuado com sucesso! Por favor verifique o seu email.',
            'user' => $user
        ], 201);
    }

    // ---------------------------
    // LOGIN
    // ---------------------------
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $user = $request->user();

            if (!$user->hasVerifiedEmail()) {
                return response()->json(['message' => 'Email não verificado.'], 403);
            }

            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user' => $user
            ]);
        }

        return response()->json([
            'message' => 'Email ou password inválida.'
        ], 401);
    }

    // ---------------------------
    // LOGOUT
    // ---------------------------
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout efetuado com sucesso'
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
