<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Liste des notifications de l'utilisateur courant
     */
    public function index(): JsonResponse
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->latest()
            ->paginate(20);

        return response()->json(['data' => $notifications]);
    }

    /**
     * Nombre de non lues
     */
    public function unreadCount(): JsonResponse
    {
        $count = Notification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead(Notification $notification): JsonResponse
    {
        abort_unless($notification->user_id === Auth::id(), 403);
        $notification->update(['read_at' => now()]);
        return response()->json(['message' => 'Notification lue']);
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead(): JsonResponse
    {
        Notification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        return response()->json(['message' => 'Toutes les notifications sont marquées comme lues']);
    }
}
