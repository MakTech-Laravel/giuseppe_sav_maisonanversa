<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
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
