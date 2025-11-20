<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
   public function update(Request $request, User $user)
{
    // 1. Validação
    $request->validate([
        'name' => 'required|string|max:255',
        // 'file' deve ser uma imagem, max 2MB (2048kb)
        'file' => 'nullable|image|max:2048', 
    ]);

    $data = $request->only(['name']);

    // 2. Upload da Imagem
    if ($request->hasFile('file')) {
        
     
        if ($user->photo_avatar_filename && Storage::disk('public')->exists('photos_avatars/' . $user->photo_avatar_filename)) {
             Storage::disk('public')->delete('photos_avatars/' . $user->photo_avatar_filename);
        }

        // Guardar a nova imagem na pasta 'storage/app/public/photos_avatars'
        $path = $request->file('file')->store('photos_avatars', 'public');
        
     
        $data['photo_avatar_filename'] = basename($path);
    }

 
    if ($request->filled('password')) {
        $data['password'] = Hash::make($request->password);
    }

    $user->update($data);

    return response()->json([
        'data' => $user, 
        'message' => 'Perfil atualizado!'
    ]);
}
}