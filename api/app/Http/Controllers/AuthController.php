<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

        // 1. Criar o Utilizador com 10 moedas (Bónus de Boas-vindas)
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'coins_balance' => 10, // Começa com 10 moedas
        ]);

        // 2. REGISTAR A TRANSAÇÃO NO HISTÓRICO (O que faltava)
        // Usamos o ID 1 porque definimos antes que 1 = Bonus
        CoinTransaction::create([
            'user_id' => $user->id,
            'coin_transaction_type_id' => 1,
            'coin_amount' => 10,
        ]);

        // 3. Dispara o evento de Registo (para enviar email de verificação)
        event(new Registered($user));

        return response()->json([
            'message' => 'Registo efetuado com sucesso! Por favor verifique o seu email para ativar a conta.',
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

            // VERIFICAÇÃO DE SEGURANÇA: Impede login se não validou email
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
