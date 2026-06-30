<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /**
     * Get all unread notifications for the authenticated user.
     */
    public function index()
    {
        $notifications = auth()->user()->unreadNotifications;

        return response()->json([
            'success'       => true,
            'notifications' => $notifications->map(function ($notification) {
                return [
                    'id'         => $notification->id,
                    'data'       => $notification->data,
                    'created_at' => $notification->created_at->diffForHumans(),
                ];
            }),
            'unread_count'  => $notifications->count(),
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead($id)
    {
        $notification = DatabaseNotification::findOrFail($id);

        // Otorisasi: Pastikan notifikasi ini milik user yang sedang login
        if ((string) $notification->notifiable_id !== (string) auth()->id() || 
            $notification->notifiable_type !== get_class(auth()->user())) {
            abort(403, 'Tindakan tidak diizinkan. Notifikasi ini bukan milik Anda.');
        }

        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    /**
     * Mark all unread notifications of the current user as read.
     */
    public function markAllAsRead()
    {
        // Secara implisit ter-scoped ke user login saat ini
        auth()->user()->unreadNotifications->markAsRead();

        return response()->json(['success' => true]);
    }
}
