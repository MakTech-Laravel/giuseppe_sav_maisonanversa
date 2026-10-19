<?php

namespace App\Http\Controllers\Admin\ServerMonitoring;

use App\Http\Controllers\Controller;
use App\Monitoring\Enums\RestartOutcome;
use App\Monitoring\RestartService;
use App\Monitoring\ServiceRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class RestartController extends Controller
{
    public function __invoke(
        Request $request,
        string $locale,
        string $service,
        RestartService $restarts,
        ServiceRegistry $registry,
    ): JsonResponse {
        try {
            $logical = $registry->resolve($service);
        } catch (InvalidArgumentException) {
            return response()->json([
                'status' => false,
                'message' => 'Unknown service.',
            ], 404);
        }

        $result = $restarts->restart(
            service: $logical,
            trigger: 'manual',
            operator: $request->user(),
            reason: 'Manual restart from Reverb Server',
            verifyHealth: true,
        );

        $statusCode = match ($result['outcome']) {
            RestartOutcome::Succeeded => 200,
            RestartOutcome::SucceededUnhealthy => 200,
            RestartOutcome::Rejected => 409,
            RestartOutcome::CoolifyUnavailable => 503,
            RestartOutcome::Failed => 502,
            default => 202,
        };

        return response()->json([
            'status' => in_array($result['outcome'], [RestartOutcome::Succeeded, RestartOutcome::SucceededUnhealthy, RestartOutcome::Requested, RestartOutcome::InProgress], true),
            'message' => $result['message'],
            'data' => [
                'service' => $logical->value,
                'outcome' => $result['outcome']->value,
                'health_verified' => $result['health_verified'] ?? null,
                'restart_id' => $result['restart_id'] ?? null,
                'deployment_uuid' => $result['deployment_uuid'] ?? null,
            ],
        ], $statusCode);
    }

    public function restartBoth(
        Request $request,
        string $locale,
        RestartService $restarts,
    ): JsonResponse {
        $request->validate([
            'confirm' => ['required', 'accepted'],
        ]);

        $results = $restarts->restartBoth($request->user());

        return response()->json([
            'status' => true,
            'message' => 'Restart both requested. Review per-service outcomes.',
            'data' => [
                'main-app' => [
                    'outcome' => $results['main-app']['outcome']->value,
                    'message' => $results['main-app']['message'],
                    'health_verified' => $results['main-app']['health_verified'] ?? null,
                ],
                'reverb' => [
                    'outcome' => $results['reverb']['outcome']->value,
                    'message' => $results['reverb']['message'],
                    'health_verified' => $results['reverb']['health_verified'] ?? null,
                ],
            ],
        ]);
    }
}
