<?php

use App\Enums\RoleEnum;
use App\Models\Inquiry;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->admin = User::factory()->admin()->create();
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $this->admin->syncTypeFromRoles();
});

test('staff can view appointments newest first with filters', function () {
    $older = Inquiry::factory()->appointment()->create();
    $newer = Inquiry::factory()->consult()->create();
    Inquiry::factory()->feedback()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.appointments.index', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/appointments/index')
            ->has('inquiries.data', 2)
            ->where('inquiries.data.0.id', (string) $newer->id)
            ->where('inquiries.data.1.id', (string) $older->id)
            ->where('filters.search', '')
            ->where('filters.status', '')
            ->where('filters.kind', '')
            ->where('filters.per_page', 15)
            ->has('perPageOptions', 5)
            ->has('kinds', 3)
        );
});

test('staff can paginate and filter appointments by unseen status', function () {
    Inquiry::factory()->appointment()->count(16)->create();
    Inquiry::factory()->appointment()->seen()->create(['name' => 'Gezien Bezoeker']);

    $this->actingAs($this->admin)
        ->get(route('admin.appointments.index', ['locale' => 'nl', 'per_page' => 10]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('inquiries.data', 10)
            ->where('inquiries.last_page', 2)
            ->where('inquiries.total', 17)
        );

    $this->actingAs($this->admin)
        ->get(route('admin.appointments.index', [
            'locale' => 'nl',
            'status' => 'unseen',
            'per_page' => 50,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('inquiries.data', 16)
            ->where('inquiries.total', 16)
            ->where('filters.status', 'unseen')
        );
});

test('staff can view feedback without appointment rows', function () {
    Inquiry::factory()->appointment()->create();
    $feedback = Inquiry::factory()->feedback()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.feedback.index', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/feedback/index')
            ->has('inquiries.data', 1)
            ->where('inquiries.data.0.id', (string) $feedback->id)
        );
});

test('opening an appointment marks it seen', function () {
    $inquiry = Inquiry::factory()->appointment()->create();

    expect($inquiry->seen_at)->toBeNull();

    $this->actingAs($this->admin)
        ->get(route('admin.appointments.show', ['locale' => 'nl', 'inquiry' => $inquiry->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/appointments/show')
            ->where('inquiry.id', (string) $inquiry->id)
            ->where('inquiry.seen', true)
        );

    expect($inquiry->fresh()->seen_at)->not->toBeNull();
});

test('staff can toggle seen status', function () {
    $inquiry = Inquiry::factory()->appointment()->seen()->create();

    $this->actingAs($this->admin)
        ->patch(route('admin.appointments.seen', ['locale' => 'nl', 'inquiry' => $inquiry->id]), [
            'seen' => false,
        ])
        ->assertRedirect();

    expect($inquiry->fresh()->seen_at)->toBeNull();

    $this->actingAs($this->admin)
        ->patch(route('admin.appointments.seen', ['locale' => 'nl', 'inquiry' => $inquiry->id]), [
            'seen' => true,
        ])
        ->assertRedirect();

    expect($inquiry->fresh()->seen_at)->not->toBeNull();
});

test('staff can delete an appointment', function () {
    $inquiry = Inquiry::factory()->appointment()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.appointments.destroy', ['locale' => 'nl', 'inquiry' => $inquiry->id]))
        ->assertRedirect(route('admin.appointments.index', ['locale' => 'nl']));

    expect(Inquiry::query()->find($inquiry->id))->toBeNull();
});

test('feedback cannot be opened on the appointments show page', function () {
    $feedback = Inquiry::factory()->feedback()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.appointments.show', ['locale' => 'nl', 'inquiry' => $feedback->id]))
        ->assertNotFound();
});

test('customers cannot open the appointments inbox', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->get(route('admin.appointments.index', ['locale' => 'nl']))
        ->assertForbidden();
});

test('priority inquiries appear first in the appointments inbox', function () {
    $olderPriority = Inquiry::factory()->appointment()->priority()->create([
        'name' => 'Priority Member',
    ]);
    $newerRegular = Inquiry::factory()->appointment()->create([
        'name' => 'Regular Guest',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.appointments.index', ['locale' => 'nl']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('inquiries.data', 2)
            ->where('inquiries.data.0.id', (string) $olderPriority->id)
            ->where('inquiries.data.0.priority', true)
            ->has('inquiries.data.0.sla_due_at')
            ->where('inquiries.data.0.sla_breached', false)
            ->where('inquiries.data.1.id', (string) $newerRegular->id)
            ->where('inquiries.data.1.priority', false)
        );
});
