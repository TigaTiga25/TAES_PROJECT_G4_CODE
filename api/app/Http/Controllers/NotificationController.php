<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\User;

class NotificationController extends Controller
{
    /**
     * Retorna todas as notificações não lidas do utilizador autenticado.
     * Usado pelo Polling no Vue.
     */
    public function getUnread(Request $request)
    {
        $user = $request->user();

        // Se por acaso o user não estiver logado (ex: Anonymous Mode),
        // não deve receber notificações pessoais.
        if (!$user) {
            return response()->json([]);
        }

        $notifications = $user->notifications()
                              ->where('read', false)
                              ->latest()
                              ->get();

        return response()->json($notifications);
    }

    /**
     * Marca uma notificação específica como lida.
     */
    public function markAsRead(Request $request, $id)
    {
        // Garante que a notificação pertence mesmo ao user logado (Segurança)
        $notification = $request->user()
                                ->notifications()
                                ->find($id);

        if ($notification) {
            $notification->update(['read' => true]);
            return response()->json(['message' => 'Notification marked as read']);
        }

        return response()->json(['message' => 'Notification not found'], 404);
    }

    /**
     * Rota de DEBUG/TESTE.
     * Serve para simular o CSS a enviar um evento (Novo Líder, etc).
     */
    public function createMock(Request $request)
    {
        // Para testes: tenta pegar o user logado, senão pega o user com ID 1
        $user = $request->user() ?? User::find(1);

        if (!$user) {
             return response()->json(['error' => 'Cria pelo menos um User na BD primeiro!'], 400);
        }

        $notification = Notification::create([
            'user_id' => $user->id,
            'type'    => $request->input('type', 'LEADERBOARD'), 
            'title'   => $request->input('title', 'Novo Líder Global!'),
            'message' => $request->input('message', 'O jogador ' . $user->name . ' bateu o recorde!'),
            'read'    => false
        ]);

        return response()->json([
            'message' => 'Notificação de teste criada!',
            'data' => $notification
        ]);
    }
}
