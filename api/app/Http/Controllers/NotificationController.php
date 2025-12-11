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
        // 1. Capturar dados do pedido
        $type    = $request->input('type', 'LEADERBOARD');
        $title   = $request->input('title', 'New Global Leader!');
        $message = $request->input('message', 'A player has broken the record!');

        // 2. Determinar o ALVO (Target)
        // Se o tipo for LEADERBOARD ou CUSTOMIZATION, manda para TODOS.
        // Se for outro, manda só para o user atual (para testes simples).

        if ($type === 'LEADERBOARD' || $type === 'CUSTOMIZATION' || str_starts_with($type, 'SHOP_')) {

            // --- MODO GLOBAL (Broadcast) ---
            $users = User::all();
            $count = 0;

            foreach ($users as $user) {
                Notification::create([
                    'user_id' => $user->id,
                    'type'    => $type,
                    'title'   => $title,
                    'message' => $message,
                    'read'    => false
                ]);
                $count++;
            }

            return response()->json([
                'message' => "Global notification sent to {$count} users!",
                'type' => $type
            ]);

        } else {

            // --- MODO INDIVIDUAL (Single User) ---
            $user = $request->user() ?? User::find(1);

            $notification = Notification::create([
                'user_id' => $user->id,
                'type'    => $type,
                'title'   => $title,
                'message' => $message,
                'read'    => false
            ]);

            return response()->json([
                'message' => 'Personal notification created!',
                'data' => $notification
            ]);
        }
    }
}
