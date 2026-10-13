<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\Notifications\InboxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public const PER_PAGE = 20;

    public function __construct(private readonly InboxService $inbox) {}

    public function index(Request $request, string $locale): JsonResponse
    {
        $notifications = $this->inbox->paginateFor($request->user(), [], self::PER_PAGE);

        return response()->json([
            'data' => array_map(fn (array $item): array => [
                ...$item,
                'body' => $item['message'],
            ], $notifications->items()),
            'unread_count' => $this->inbox->unreadCount($request->user()),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
            ],
        ]);
    }

    public function markAsRead(Request $request, string $locale, string $notification): RedirectResponse
    {
        $row = $this->inbox->findFor($request->user(), $notification);
        $this->inbox->markRead($request->user(), $row);

        return back();
    }

    public function markAllAsRead(Request $request, string $locale): RedirectResponse
    {
        $this->inbox->markAllRead($request->user());

        return back();
    }
}
