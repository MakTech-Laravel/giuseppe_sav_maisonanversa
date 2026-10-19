<?php

namespace App\Http\Controllers\Admin\ServerMonitoring;

use App\Http\Controllers\Controller;
use App\Models\Monitoring\MonitoringServiceState;
use App\Monitoring\AutoRecoveryService;
use App\Monitoring\DashboardPresenter;
use App\Monitoring\Enums\MonitoredServiceId;
use App\Monitoring\HealthCheckRunner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServerMonitoringController extends Controller
{
    public function index(string $locale, DashboardPresenter $presenter): Response
    {
        return Inertia::render('admin/servers-maintenance/index', $presenter->present());
    }

    public function runChecks(string $locale, HealthCheckRunner $runner): RedirectResponse
    {
        $runner->runAll();

        return back()->with('success', 'Health checks completed.');
    }

    public function updateSettings(
        Request $request,
        string $locale,
        AutoRecoveryService $recovery,
    ): RedirectResponse {
        $request->validate([
            'global_enabled' => ['required', 'boolean'],
            'reverb_enabled' => ['required', 'boolean'],
        ]);

        $recovery->setGlobalEnabled($request->boolean('global_enabled'));

        // Main-app auto-recovery is intentionally disabled (Reverb-only monitoring).
        $main = MonitoringServiceState::forService(MonitoredServiceId::MainApp);
        $main->auto_recovery_enabled = false;
        $main->save();

        $reverb = MonitoringServiceState::forService(MonitoredServiceId::Reverb);
        $reverb->auto_recovery_enabled = $request->boolean('reverb_enabled');
        $reverb->save();

        return back()->with('success', 'Recovery settings updated.');
    }
}
