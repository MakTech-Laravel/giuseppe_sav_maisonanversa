<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public const PER_PAGE = 20;

    public function index(Request $request, string $locale): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(self::PER_PAGE)
            ->through(fn (DatabaseNotification $notification) => [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? null,
                'body' => $notification->data['body'] ?? null,
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'data' => $notifications->items(),
            'unread_count' => $request->user()->unreadNotifications()->count(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
            ],
        ]);
    }

    public function markAsRead(Request $request, string $locale, string $notification): RedirectResponse
    {
        /** @var DatabaseNotification|null $record */
        $record = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->first();

        abort_if($record === null, 404);

        $record->markAsRead();

        return back();
    }

    public function markAllAsRead(Request $request, string $locale): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
