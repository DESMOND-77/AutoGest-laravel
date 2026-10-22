<?php

namespace App\Domain\Notifications\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The legacy api/notifications.php marked every notification as read as a
 * side effect of the GET request that loads the dropdown - meaning simply
 * opening the bell discarded which ones were actually new. Reading and
 * marking-as-read are two separate, correctly-verbed endpoints here.
 */
class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->notifications()->latest()->limit(10)->get();

        return response()->json([
            'unread_count' => $request->user()->unreadNotifications()->count(),
            'notifications' => $notifications->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->data['title'] ?? '',
                'message' => $n->data['message'] ?? '',
                'link' => $n->data['link'] ?? null,
                'read' => $n->read_at !== null,
                'created_at' => $n->created_at->diffForHumans(),
            ]),
        ]);
    }

    public function markRead(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return $request->wantsJson() ? response()->json(['status' => 'ok']) : back();
    }
}
