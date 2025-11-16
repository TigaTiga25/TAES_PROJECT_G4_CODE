<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash; 

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

        // 3. Cria um token para o novo utilizador
        $token = $user->createToken('auth-token')->plainTextToken;

        // 4. Responde ao frontend
        return response()->json([
            'token' => $token,
            'user' => $user
        ], 201); // 201 = Created
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
            
            // 3A. SUCESSO: Email existe E password está correta
            $user = $request->user(); // Vai buscar o utilizador que fez login
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