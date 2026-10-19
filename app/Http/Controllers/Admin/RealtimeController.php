<?php

namespace App\Http\Controllers\Admin;

use App\Events\RealtimePrivateTestEvent;
use App\Events\ReverbPingEvent;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class RealtimeController extends Controller
{
    public function index(string $locale): Response
    {
        return Inertia::render('admin/realtime/index');
    }

    public function ping(Request $request, string $locale): JsonResponse
    {
        $event = new ReverbPingEvent(
            message: str((string) $request->input('message', 'Hello from Maison Anversa'))->limit(200)->toString(),
            triggeredAt: now()->toIso8601String(),
        );

        return $this->sendDiagnostic($event, ReverbPingEvent::CHANNEL, 'Public ping dispatched.');
    }

    public function testNotification(Request $request, string $locale): JsonResponse
    {
        $user = $request->user();

        $event = new RealtimePrivateTestEvent(
            user: $user,
            message: 'Private channel push sent at '.now()->toIso8601String(),
            triggeredAt: now()->toIso8601String(),
        );

        return $this->sendDiagnostic(
            $event,
            RealtimePrivateTestEvent::channelName($user->id),
            'Private test event dispatched.',
        );
    }

    /**
     * Effective broadcasting target for deployment checks — never the secret.
     */
    public function health(string $locale): JsonResponse
    {
        $connection = (string) config('broadcasting.default');
        $options = (array) config("broadcasting.connections.{$connection}.options", []);
        $key = (string) config("broadcasting.connections.{$connection}.key", '');

        return response()->json([
            'status' => true,
            'message' => 'Broadcasting configuration.',
            'data' => [
                'broadcast_connection' => $connection,
                'host' => $options['host'] ?? null,
                'port' => $options['port'] ?? null,
                'scheme' => $options['scheme'] ?? null,
                'app_id' => config("broadcasting.connections.{$connection}.app_id"),
                'app_key_preview' => $key === '' ? null : substr($key, 0, 8).'...',
            ],
        ]);
    }

    private function sendDiagnostic(ReverbPingEvent|RealtimePrivateTestEvent $event, string $channel, string $message): JsonResponse
    {
        try {
            // event() rather than broadcast(): the PendingBroadcast publishes in its
            // destructor, which would escape this try block.
            event($event);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Broadcast failed: '.$e->getMessage(),
                'data' => ['channel' => $channel, 'event' => $event->broadcastAs()],
            ], 502);
        }

        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => [
                'channel' => $channel,
                'event' => $event->broadcastAs(),
                'payload' => $event->broadcastWith(),
            ],
        ]);
    }
}
