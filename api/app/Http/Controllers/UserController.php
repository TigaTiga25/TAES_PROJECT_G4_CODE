<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    // --------------------------------------------------------------------
    // MOSTRAR PERFIL
    // --------------------------------------------------------------------
    public function show(Request $request)
    {
        return $request->user();
    }

    // --------------------------------------------------------------------
    // ATUALIZAR PERFIL
    // --------------------------------------------------------------------
    public function update(Request $request)
    {
        $user = $request->user();

        // 1. Validação
        $validated = $request->validate([
            'nickname' => 'nullable|string|max:20|unique:users,nickname,'.$user->id,
            'name' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:3',
            'file' => 'nullable|image|max:4096', // Foto de upload
            'custom_avatar_seed' => 'nullable|string' // O boneco (Felix, Aneka, etc.)
        ]);

        // 2. Atualizar Textos
        if ($request->filled('nickname')) {
            $user->nickname = $validated['nickname'];
        }

        if ($request->filled('name')) {
            $user->name = $validated['name'];
        }

        if ($request->filled('password')) {
            // O Model User tem o cast 'hashed', por isso basta atribuir direto
            $user->password = $validated['password'];
        }

        // 3. Atualizar Avatar (O Boneco)
        if ($request->filled('custom_avatar_seed')) {
            $seed = $validated['custom_avatar_seed'];

            // Avatares gratuitos que todos têm
            $defaultAvatars = ['Felix', 'Aneka', 'Zack', 'Midnight', 'Bear'];
            // Avatares que eu comprei
            $myAvatars = $user->unlocked_avatars ?? [];

            // Só deixa mudar se for gratuito OU se eu o tiver comprado
            if (in_array($seed, $defaultAvatars) || in_array($seed, $myAvatars)) {
                $user->custom_avatar_seed = $seed;
            }
        }

        // 4. Atualizar Foto (Upload)
        if ($request->hasFile('file')) {
            // Apaga a foto antiga se existir
            if ($user->photo_avatar_filename && Storage::disk('public')->exists('photos/' . $user->photo_avatar_filename)) {
                Storage::disk('public')->delete('photos/' . $user->photo_avatar_filename);
            }

            // Guarda a nova
            $path = $request->file('file')->store('photos', 'public');
            $user->photo_avatar_filename = basename($path);
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully!',
            'user' => $user
        ]);
    }

    // --------------------------------------------------------------------
    // EQUIPAR BARALHO
    // --------------------------------------------------------------------
    public function updateDeck(Request $request)
    {
        $request->validate([
            'deck' => 'required|string',
        ]);

        $user = $request->user();

        // Garante que é array (graças ao cast no Model)
        $myDecks = $user->unlocked_decks ?? [];

        // O 'default' é sempre permitido
        if ($request->deck !== 'default' && !in_array($request->deck, $myDecks)) {
            return response()->json(['message' => 'Não possuis este baralho.'], 403);
        }

        $user->current_deck = $request->deck;
        $user->save();

        return response()->json([
            'message' => 'Baralho equipado com sucesso!',
            'current_deck' => $user->current_deck
        ]);
    }
}
