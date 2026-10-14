<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Services\Notifications\InboxService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The acting admin's own in-app inbox; rows of other users resolve to 404.
 */
class NotificationController extends Controller
{
    public function __construct(private readonly InboxService $inbox) {}

    public function index(Request $request, string $locale): Response
    {
        $filters = [
            'status' => in_array($request->query('status'), InboxService::STATUSES, true)
                ? (string) $request->query('status')
                : 'all',
            'type' => NotificationType::tryFrom((string) $request->query('type', ''))?->value ?? '',
            'search' => trim((string) $request->query('search', '')),
        ];

        return Inertia::render('admin/notifications/index', [
            'rows' => $this->inbox->paginateFor($request->user(), $filters),
            'filters' => $filters,
            'types' => array_map(fn (NotificationType $type): array => [
                'value' => $type->value,
                'label' => __($type->label()),
            ], NotificationType::cases()),
            'unreadCount' => $this->inbox->unreadCount($request->user()),
        ]);
    }

    public function show(Request $request, string $locale, string $userNotification): Response
    {
        $row = $this->inbox->findFor($request->user(), $userNotification);
        $this->inbox->markRead($request->user(), $row);

        return Inertia::render('admin/notifications/show', [
            'notification' => $this->inbox->toItem($row->fresh(['notification'])),
        ]);
    }

    public function read(Request $request, string $locale, string $userNotification): RedirectResponse
    {
        $row = $this->inbox->findFor($request->user(), $userNotification);
        $this->inbox->markRead($request->user(), $row);

        return back();
    }

    public function readAll(Request $request, string $locale): RedirectResponse
    {
        $this->inbox->markAllRead($request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Alle meldingen zijn gelezen.')]);

        return back();
    }

    public function destroy(Request $request, string $locale, string $userNotification): RedirectResponse
    {
        $row = $this->inbox->findFor($request->user(), $userNotification);
        $this->inbox->delete($request->user(), $row);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Melding verwijderd.')]);

        $showUrl = route('admin.notifications.show', ['locale' => $locale, 'userNotification' => $row->id]);

        return url()->previous() === $showUrl
            ? to_route('admin.notifications.index', ['locale' => $locale])
            : back();
    }
}
