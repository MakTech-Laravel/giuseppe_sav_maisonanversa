<?php

namespace App\Console\Commands\Community;

use App\Enums\NotificationType;
use App\Enums\UserType;
use App\Mail\SessionChatDigestMail;
use App\Models\CommunitySession;
use App\Models\CommunitySessionParticipant;
use App\Models\User;
use App\Services\Community\SessionChatService;
use App\Support\MailLocale;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Fixed-interval bulk chat digest. One common “Nieuwe berichten” notice per
 * inactive member with unread — never per message, never while last_seen is fresh.
 */
#[Signature('sessions:send-chat-digests')]
#[Description('Send bulk session-chat digests to inactive members with unread messages')]
class SendSessionChatDigestsCommand extends Command
{
    public const ACTIVE_WITHIN_MINUTES = 5;

    public function handle(SessionChatService $chat): int
    {
        $activeCutoff = now()->subMinutes(self::ACTIVE_WITHIN_MINUTES);
        $sent = 0;

        $candidateIds = CommunitySessionParticipant::query()
            ->distinct()
            ->pluck('user_id')
            ->merge(CommunitySession::query()->pluck('host_id'))
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        User::query()
            ->whereIn('id', $candidateIds)
            ->where('type', UserType::Customer)
            ->where(function ($query) use ($activeCutoff): void {
                $query->whereNull('last_seen_at')
                    ->orWhere('last_seen_at', '<', $activeCutoff);
            })
            ->orderBy('id')
            ->each(function (User $user) use ($chat, &$sent): void {
                if ($this->digest($user, $chat)) {
                    $sent++;
                }
            });

        $this->info("Sent chat digests to {$sent} members.");

        return self::SUCCESS;
    }

    private function digest(User $user, SessionChatService $chat): bool
    {
        $unread = $chat->unreadTotal($user);

        if ($unread < 1) {
            return false;
        }

        $newestUnreadAt = $chat->newestUnreadAt($user);

        if ($newestUnreadAt === null) {
            return false;
        }

        if (
            $user->chat_digest_sent_at !== null
            && $newestUnreadAt->lte($user->chat_digest_sent_at)
        ) {
            return false;
        }

        $locale = MailLocale::resolve($user->locale);
        app()->setLocale($locale);

        $sessionId = $chat->sessionIdWithMostUnread($user);
        $ctaUrl = $this->chatUrl($user, $locale, $sessionId);

        $title = __('Nieuwe berichten');
        $message = trans_choice(
            'U heeft :count ongelezen chatbericht.|U heeft :count ongelezen chatberichten.',
            $unread,
            ['count' => $unread],
        );

        notify_user(
            $user,
            NotificationType::Community,
            $title,
            $message,
            [
                'link_url' => $ctaUrl,
                'link_label' => __('Bekijk berichten'),
                'unread_count' => $unread,
                'digest' => 'session_chat',
            ],
        );

        if ($user->session_emails) {
            Mail::to($user)->send(new SessionChatDigestMail(
                unreadCount: $unread,
                ctaUrl: $ctaUrl,
                locale: $locale,
            ));
        }

        $user->forceFill(['chat_digest_sent_at' => now()])->save();

        return true;
    }

    private function chatUrl(User $user, string $locale, ?int $sessionId): string
    {
        if ($user->isAdmin()) {
            return $sessionId !== null
                ? route('admin.chat.show', ['locale' => $locale, 'communitySession' => $sessionId])
                : route('admin.chat.index', ['locale' => $locale]);
        }

        return $sessionId !== null
            ? route('member.chat.show', ['locale' => $locale, 'communitySession' => $sessionId])
            : route('member.chat.index', ['locale' => $locale]);
    }
}
