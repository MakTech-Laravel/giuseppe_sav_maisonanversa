<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CommunityEventStatus;
use App\Enums\InquiryType;
use App\Enums\OrderStatus;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\CommunityEvent;
use App\Models\CommunityReport;
use App\Models\Inquiry;
use App\Models\JournalArticle;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, string $locale): Response
    {
        $nextEvent = CommunityEvent::query()
            ->whereIn('status', [CommunityEventStatus::Opening, CommunityEventStatus::Ongoing])
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->first();

        return Inertia::render('dashboard', [
            'stats' => [
                [
                    'key' => 'Klanten',
                    'value' => (string) User::query()->where('type', UserType::Customer)->count(),
                    'hintKey' => 'Lid-accounts',
                ],
                [
                    'key' => 'Beheerders',
                    'value' => (string) User::query()->where('type', UserType::Admin)->count(),
                    'hintKey' => 'Personeelsaccounts',
                ],
                [
                    'key' => 'Journal',
                    'value' => (string) JournalArticle::query()->count(),
                    'hintKey' => 'Journalartikelen',
                ],
                [
                    'key' => 'Openstaande bestellingen',
                    'value' => (string) Order::query()
                        ->whereIn('status', [OrderStatus::Paid, OrderStatus::Processing])
                        ->count(),
                    'hintKey' => 'Betaald, nog niet verzonden',
                ],
                [
                    'key' => 'Ongeziene aanvragen',
                    'value' => (string) Inquiry::query()
                        ->whereIn('type', [...InquiryType::appointmentInbox(), InquiryType::Feedback])
                        ->unseen()
                        ->count(),
                    'hintKey' => 'Afspraken en feedback samen',
                ],
                [
                    'key' => 'Openstaande meldingen',
                    'value' => (string) CommunityReport::query()->where('status', 'open')->count(),
                    'hintKey' => 'Nog niet afgehandelde communitymeldingen',
                ],
            ],
            'recentCustomers' => User::query()
                ->where('type', UserType::Customer)
                ->latest()
                ->limit(5)
                ->get(['id', 'name', 'email', 'username', 'created_at'])
                ->map(fn (User $customer) => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'username' => $customer->username,
                    'created_at' => $customer->created_at?->toDateString(),
                ]),
            'nextCommunityEvent' => $nextEvent === null ? null : [
                'id' => (string) $nextEvent->id,
                'title' => $nextEvent->translated('title'),
                'starts_at' => $nextEvent->starts_at?->toIso8601String(),
            ],
            'staffName' => $request->user()?->name ?? '',
        ]);
    }
}
