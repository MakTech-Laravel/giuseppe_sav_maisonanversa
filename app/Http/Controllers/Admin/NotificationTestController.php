<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendTestNotificationRequest;
use App\Services\Notifications\NotificationTestService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class NotificationTestController extends Controller
{
    public function __construct(private readonly NotificationTestService $tester) {}

    public function index(string $locale): Response
    {
        return Inertia::render('admin/notifications/test', [
            'types' => array_map(fn (NotificationType $type): array => [
                'value' => $type->value,
                'label' => __($type->label()),
            ], NotificationType::cases()),
            'queueConnection' => (string) config('queue.default'),
            'broadcastConnection' => (string) config('broadcasting.default'),
        ]);
    }

    public function send(SendTestNotificationRequest $request, string $locale): JsonResponse
    {
        try {
            $result = $this->tester->send($request->user(), $request->validated());
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Notification failed: '.$e->getMessage(),
            ], 502);
        }

        return response()->json([
            'status' => true,
            'message' => $result['mode'] === 'queue'
                ? 'Notification stored; realtime fan-out queued (needs a queue worker).'
                : 'Notification stored and broadcast directly.',
            'data' => $result,
        ]);
    }
}
