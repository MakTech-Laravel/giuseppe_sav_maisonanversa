<?php

use App\Enums\RoleEnum;
use App\Models\CommunitySession;
use App\Models\CommunitySessionMessage;
use App\Models\CommunitySessionParticipant;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $this->host = User::factory()->create();
    $this->player = User::factory()->create();
    $this->session = CommunitySession::factory()->create([
        'host_id' => $this->host->id,
        'starts_at' => now()->addDay(),
    ]);

    foreach ([$this->host, $this->player] as $user) {
        CommunitySessionParticipant::factory()->create([
            'community_session_id' => $this->session->id,
            'user_id' => $user->id,
        ]);
    }

    $this->message = CommunitySessionMessage::factory()->create([
        'community_session_id' => $this->session->id,
        'user_id' => $this->host->id,
        'body' => 'Hallo',
    ]);
});

test('a member can report another player message', function () {
    $this->actingAs($this->player)
        ->post(route('member.chat.messages.report', [
            'locale' => 'nl',
            'communitySession' => $this->session->id,
            'message' => $this->message->id,
        ]))
        ->assertRedirect();

    expect($this->message->fresh()->isReported())->toBeTrue()
        ->and($this->message->fresh()->reported_by_id)->toBe($this->player->id);
});

test('admins can delete a chat message', function () {
    $admin = User::factory()->admin()->create();
    $admin->assignRole(RoleEnum::SUPER_ADMIN->value);
    $admin->syncTypeFromRoles();

    $this->actingAs($admin)
        ->delete(route('admin.chat.messages.destroy', [
            'locale' => 'nl',
            'communitySession' => $this->session->id,
            'message' => $this->message->id,
        ]))
        ->assertRedirect();

    expect(CommunitySessionMessage::query()->find($this->message->id))->toBeNull();
});
