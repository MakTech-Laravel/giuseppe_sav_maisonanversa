<?php

use App\Console\Commands\Community\SendSampleSessionEmailsCommand;
use App\Mail\SessionChatDigestMail;
use App\Mail\SessionNoticeMail;
use App\Models\Club;
use App\Models\CommunitySession;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('sessions:send-sample-emails sends the six client sample mails', function () {
    Mail::fake();

    $host = User::factory()->create();
    CommunitySession::factory()->create([
        'host_id' => $host->id,
        'club_id' => Club::factory()->create()->id,
        'starts_at' => now()->addDays(2)->setTime(10, 30),
    ]);

    $this->artisan(SendSampleSessionEmailsCommand::class, [
        'email' => 'qa@example.com',
        '--locale' => 'nl',
    ])->assertSuccessful();

    Mail::assertQueued(SessionNoticeMail::class, 5);
    Mail::assertQueued(SessionChatDigestMail::class, 1);
});
