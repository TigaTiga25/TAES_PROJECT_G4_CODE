<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;

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


    // ---------------------------
    // LOGIN (CORRIGIDO)
    // ---------------------------
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {

            // 3A. SUCESSO: Email existe E password está correta
            $user = $request->user(); // Vai buscar o utilizador que fez login
            $token = $user->createToken('auth-token')->plainTextToken; // Cria um novo token

            // 🔥 AQUI ESTÁ A CORREÇÃO — DEVOLVE TAMBÉM O USER
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
}
