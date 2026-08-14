<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\RoleEnum;
use App\Exports\NewsletterSubscribersExport;
use App\Http\Controllers\Controller;
use App\Mail\ShippingNotification;
use App\Models\CommunityEvent;
use App\Models\CommunityPost;
use App\Models\CommunityReport;
use App\Models\EditionPiece;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\User;
use App\Services\Edition\EditionInventory;
use App\Support\OrderPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OpsController extends Controller
{
    public function orders(Request $request, string $locale, OrderPresenter $presenter): Response
    {
        $orders = Order::query()
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (Order $order) => [
                ...$presenter->summary($order),
                'customer' => $order->name,
            ]);

        return Inertia::render('admin/orders/index', [
            'orders' => $orders,
            'commerceConnected' => true,
        ]);
    }

    public function orderShow(Request $request, string $locale, Order $order, OrderPresenter $presenter): Response
    {
        $detail = $presenter->detail($order);
        $detail['customer'] = $order->name;

        return Inertia::render('admin/orders/show', [
            'order' => $detail,
            'commerceConnected' => true,
        ]);
    }

    public function updateOrderStatus(Request $request, string $locale, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:shipped,delivered,refunded'],
        ]);

        $status = OrderStatus::from($data['status']);

        $order->fill(['status' => $status]);

        if ($status === OrderStatus::Shipped && $order->shipped_at === null) {
            $order->shipped_at = now();
            Mail::to($order->email)->locale($order->locale)->queue(new ShippingNotification($order));
        }

        if ($status === OrderStatus::Delivered && $order->delivered_at === null) {
            $order->delivered_at = now();
        }

        $order->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Bestelstatus bijgewerkt.')]);

        return back();
    }

    public function community(Request $request, string $locale): Response
    {
        $items = CommunityReport::query()
            ->with(['post', 'reporter'])
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (CommunityReport $report) => [
                'id' => (string) $report->id,
                'post_id' => $report->community_post_id,
                'reporter' => $report->reporter->name,
                'reason' => $report->reason,
                'status' => $report->status,
            ]);

        return Inertia::render('admin/community/index', [
            'items' => $items,
            'posts' => CommunityPost::query()
                ->with('author')
                ->latest()
                ->limit(50)
                ->get()
                ->map(fn (CommunityPost $post) => [
                    'id' => (string) $post->id,
                    'author' => $post->author->name,
                    'content' => $post->content,
                    'is_official' => $post->is_official,
                    'status' => $post->status,
                ]),
            'communityConnected' => true,
        ]);
    }

    public function storeOfficialPost(Request $request, string $locale): RedirectResponse
    {
        $data = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
        ]);

        CommunityPost::query()->create([
            'author_id' => $request->user()->id,
            'content' => $data['content'],
            'is_official' => true,
            'status' => 'published',
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Officieel bericht geplaatst.')]);

        return back();
    }

    public function hideCommunityPost(Request $request, string $locale, CommunityPost $communityPost): RedirectResponse
    {
        $communityPost->update([
            'status' => 'hidden',
            'hidden_at' => now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Bericht verborgen.')]);

        return back();
    }

    public function letter(Request $request, string $locale): Response
    {
        $subscribers = NewsletterSubscriber::query()
            ->latest()
            ->limit(200)
            ->get()
            ->map(fn (NewsletterSubscriber $subscriber) => [
                'email' => $subscriber->email,
                'name' => $subscriber->name ?: $subscriber->email,
                'status' => $subscriber->status->value,
                'joined_at' => $subscriber->consent_at?->toDateString() ?? $subscriber->created_at?->toDateString(),
                'locale' => $subscriber->locale,
                'synced_at' => $subscriber->synced_at?->toDateTimeString(),
            ]);

        return Inertia::render('admin/letter/index', [
            'subscribers' => $subscribers,
            'letterConnected' => filled(config('services.brevo.api_key')),
        ]);
    }

    public function exportLetter(): BinaryFileResponse
    {
        return Excel::download(new NewsletterSubscribersExport, 'heritage-letter.csv');
    }

    public function events(Request $request, string $locale): Response
    {
        $events = CommunityEvent::query()->orderBy('starts_at')->get();

        return Inertia::render('admin/events/index', [
            'events' => $events->map(fn (CommunityEvent $event) => [
                'id' => (string) $event->id,
                'title' => $event->title,
                'starts_at' => $event->starts_at->toIso8601String(),
                'location' => $event->location,
            ]),
            'eventsConnected' => true,
        ]);
    }

    public function eventShow(Request $request, string $locale, CommunityEvent $event): Response
    {
        $event->load('rsvps.user');

        return Inertia::render('admin/events/show', [
            'event' => [
                'id' => (string) $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'starts_at' => $event->starts_at->toIso8601String(),
                'location' => $event->location,
                'guest_list' => $event->rsvps->map(fn ($rsvp) => $rsvp->user->name)->all(),
            ],
            'eventsConnected' => true,
        ]);
    }

    public function circle(Request $request, string $locale): Response
    {
        $members = User::query()
            ->role(RoleEnum::FOUNDING_CIRCLE->value)
            ->get()
            ->map(fn (User $user) => [
                'id' => (string) $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);

        return Inertia::render('admin/circle/index', [
            'members' => $members,
            'circleConnected' => true,
        ]);
    }

    public function circleShow(Request $request, string $locale, User $member): Response
    {
        return Inertia::render('admin/circle/show', [
            'member' => [
                'id' => (string) $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'benefits' => [__('Digital Heritage Passport'), __('Founding Circle Card')],
            ],
            'circleConnected' => true,
        ]);
    }

    public function heritage(Request $request, string $locale, EditionInventory $inventory): Response
    {
        $snapshot = $inventory->snapshot();
        $pieces = EditionPiece::query()->orderBy('edition_number')->get();

        return Inertia::render('admin/heritage/index', [
            'inventory' => [
                'product_name' => config('maison.checkout.product_name'),
                'total' => $snapshot['total'],
                'reserved' => $snapshot['reserved'],
                'available' => $snapshot['available'],
                'rows' => $pieces->map(fn (EditionPiece $piece) => [
                    'sku' => 'HE-'.$piece->formattedNumber(),
                    'label' => 'No.'.$piece->formattedNumber(),
                    'status' => $piece->status->value,
                    'status_key' => $piece->status->value,
                    'notes' => $piece->notes ?? '',
                ]),
            ],
            'heritageConnected' => true,
        ]);
    }
}
