<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\Notifications\InboxService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The member's own in-app inbox; rows of other users resolve to 404.
 */
class NotificationController extends Controller
{
    public const PER_PAGE = 15;

    public function __construct(private readonly InboxService $inbox) {}

    public function index(Request $request, string $locale): Response
    {
        $filters = [
            'status' => in_array($request->query('status'), InboxService::STATUSES, true)
                ? (string) $request->query('status')
                : 'all',
        ];

        return Inertia::render('member/notifications/index', [
            'rows' => $this->inbox->paginateFor($request->user(), $filters, self::PER_PAGE),
            'filters' => $filters,
            'unreadCount' => $this->inbox->unreadCount($request->user()),
        ]);
    }

    public function show(Request $request, string $locale, string $userNotification): Response
    {
        $row = $this->inbox->findFor($request->user(), $userNotification);
        $this->inbox->markRead($request->user(), $row);

        return Inertia::render('member/notifications/show', [
            'notification' => $this->inbox->toItem($row->fresh(['notification'])),
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

    public function destroy(Request $request, string $locale, string $userNotification): RedirectResponse
    {
        $row = $this->inbox->findFor($request->user(), $userNotification);
        $this->inbox->delete($request->user(), $row);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Melding verwijderd.')]);

        $showUrl = route('member.notifications.show', ['locale' => $locale, 'userNotification' => $row->id]);

        return url()->previous() === $showUrl
            ? to_route('member.notifications.index', ['locale' => $locale])
            : back();
    }
}
