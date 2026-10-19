<?php

use App\Enums\PermissionEnum;
use App\Models\Monitoring\MonitoringServiceState;
use App\Models\Monitoring\MonitoringSetting;
use App\Models\User;
use App\Monitoring\Enums\MonitoredServiceId;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    config(['maison.admin_type_grants_all_permissions' => false]);
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->staff = User::factory()->admin()->create();
    $this->staff->givePermissionTo(PermissionEnum::SERVERS_MAINTENANCE_RESTART->value);
});

test('staff without restart permission cannot update recovery settings', function () {
    $staff = User::factory()->admin()->create();
    $staff->givePermissionTo(PermissionEnum::DASHBOARD_VIEW->value);

    $this->actingAs($staff)
        ->post(route('admin.servers-maintenance.settings', ['locale' => 'nl']), [
            'global_enabled' => 1,
            'reverb_enabled' => 0,
        ])
        ->assertForbidden();
});

test('staff can enable reverb auto recovery using 0 and 1 flags', function () {
    $this->actingAs($this->staff)
        ->post(route('admin.servers-maintenance.settings', ['locale' => 'nl']), [
            'global_enabled' => 1,
            'reverb_enabled' => 1,
        ])
        ->assertRedirect();

    expect(MonitoringSetting::getBool('auto_recovery_global'))->toBeTrue()
        ->and(MonitoringServiceState::forService(MonitoredServiceId::MainApp)->auto_recovery_enabled)->toBeFalse()
        ->and(MonitoringServiceState::forService(MonitoredServiceId::Reverb)->auto_recovery_enabled)->toBeTrue();
});

test('staff can disable all auto recovery flags including falsey zeros', function () {
    MonitoringSetting::put('auto_recovery_global', true);
    MonitoringServiceState::forService(MonitoredServiceId::MainApp)->update(['auto_recovery_enabled' => true]);
    MonitoringServiceState::forService(MonitoredServiceId::Reverb)->update(['auto_recovery_enabled' => true]);

    $this->actingAs($this->staff)
        ->post(route('admin.servers-maintenance.settings', ['locale' => 'nl']), [
            'global_enabled' => 0,
            'reverb_enabled' => 0,
        ])
        ->assertRedirect();

    expect(MonitoringSetting::getBool('auto_recovery_global'))->toBeFalse()
        ->and(MonitoringServiceState::forService(MonitoredServiceId::MainApp)->auto_recovery_enabled)->toBeFalse()
        ->and(MonitoringServiceState::forService(MonitoredServiceId::Reverb)->auto_recovery_enabled)->toBeFalse();
});

test('saving settings always forces main app auto recovery off', function () {
    MonitoringServiceState::forService(MonitoredServiceId::MainApp)->update(['auto_recovery_enabled' => true]);

    $this->actingAs($this->staff)
        ->post(route('admin.servers-maintenance.settings', ['locale' => 'nl']), [
            'global_enabled' => 1,
            'reverb_enabled' => 1,
            'main_app_enabled' => 1,
        ])
        ->assertRedirect();

    expect(MonitoringServiceState::forService(MonitoredServiceId::MainApp)->auto_recovery_enabled)->toBeFalse()
        ->and(MonitoringServiceState::forService(MonitoredServiceId::Reverb)->auto_recovery_enabled)->toBeTrue();
});
