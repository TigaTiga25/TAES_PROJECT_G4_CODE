<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;

class AuthController extends Controller
{
    /**
     * Regista um novo utilizador.
     */
    public function register(Request $request)
    {
        // 1. Valida os dados (nome, email único, password)
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users', // 'unique:users' impede registar email repetido
            'password' => 'required|string|min:8',
        ]);

        // 2. Cria o utilizador
        // O User model (com 'password' => 'hashed') vai
        // encriptar a password automaticamente.
      // Dentro da função register() no AuthController.php
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
        ]);

        // 3. Dispara o evento de Registo (para enviar email de verificação)
        event(new Registered($user));

        // Return a message telling frontend to show "Check your email"
        return response()->json([
            'message' => 'Registo efetuado com sucesso! Por favor verifique o seu email para ativar a conta.',
            'user' => $user
        ], 201);
    }

    /**
     * Gere um pedido de autenticação (Login).
     */
    public function login(Request $request)
    {
        // 1. Valida os dados recebidos (só precisa de email e password)
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // 2. Tenta fazer a autenticação
        // Auth::attempt() VERIFICA SE O EMAIL EXISTE E SE A PASSWORD BATE CERTO.
        if (Auth::attempt($credentials)) {
            // Vai buscar o utilizador que fez login
            $user = $request->user();

            //Check if email is verified before allowing login
            if (!$user->hasVerifiedEmail()) {
                return response()->json(['message' => 'Email não verificado.'], 403);
            }
            //SUCESSO: Gera um token de autenticação
            $token = $user->createToken('auth-token')->plainTextToken; // Cria um novo token

            // Responde ao frontend com o token
            return response()->json(['token' => $token]);

        }

        // 3B. FALHA: Email não existe OU password está errada
        // Se Auth::attempt() falhar, o código continua para aqui
        return response()->json([
            'message' => 'Email ou password inválida.'
        ], 401); // 401 = Erro de Não Autorizado
    }
    public function logout(Request $request)
    {
        // O Laravel sabe quem é o utilizador através do token enviado
        //Apaga apenas o token que foi usado neste pedido
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout efetuado com sucesso'
        ]);
    }
}
