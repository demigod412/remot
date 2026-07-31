<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /** GET /api/v1/notifications — the in-app feed (bell icon). */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $items = UserNotification::where('user_id', $userId)
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => $items->map(fn ($n) => [
                'id'         => $n->id,
                'type'       => $n->type,
                'title'      => $n->title,
                'body'       => $n->body,
                'url'        => $n->url,
                'icon'       => $n->icon,
                'read_at'    => $n->read_at,
                'created_at' => $n->created_at,
            ]),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page'    => $items->lastPage(),
                'total'        => $items->total(),
                'unread'       => UserNotification::where('user_id', $userId)->unread()->count(),
            ],
        ]);
    }

    /** POST /api/v1/notifications/{id}/read — mark a single notification read. */
    public function markRead(Request $request, int $id): JsonResponse
    {
        UserNotification::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Notification marked as read.']);
    }

    /** POST /api/v1/notifications/read-all (and legacy /mark-read). */
    public function markAllRead(Request $request): JsonResponse
    {
        UserNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }
}
