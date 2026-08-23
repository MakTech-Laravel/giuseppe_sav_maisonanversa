<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\ProductType;
use App\Enums\RoleEnum;
use App\Exports\NewsletterSubscribersExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignCircleMemberRequest;
use App\Http\Requests\Admin\ResolveCommunityReportRequest;
use App\Http\Requests\Admin\UpdateHeritageProductRequest;
use App\Mail\ShippingNotification;
use App\Models\CommunityPost;
use App\Models\CommunityReport;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Checkout\OrderFulfillment;
use App\Services\Edition\EditionInventory;
use App\Support\CommunityPostPresenter;
use App\Support\OrderPresenter;
use App\Support\PassportPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OpsController extends Controller
{
    private const COMMUNITY_POSTS_PER_PAGE = 15;

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
        ]);
    }

    public function orderShow(Request $request, string $locale, Order $order, OrderPresenter $presenter): Response
    {
        $detail = $presenter->detail($order);
        $detail['customer'] = $order->name;

        return Inertia::render('admin/orders/show', [
            'order' => $detail,
        ]);
    }

    public function updateOrderStatus(
        Request $request,
        string $locale,
        Order $order,
        OrderFulfillment $fulfillment,
    ): RedirectResponse {
        $data = $request->validate([
            'status' => ['required', 'in:shipped,delivered,refunded'],
        ]);

        $status = OrderStatus::from($data['status']);

        if ($status === OrderStatus::Refunded) {
            try {
                $fulfillment->refund($order);
            } catch (ApiErrorException) {
                Inertia::flash('toast', [
                    'type' => 'error',
                    'message' => __('Stripe-terugbetaling mislukt. Probeer opnieuw.'),
                ]);

                return back();
            }

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Bestelstatus bijgewerkt.')]);

            return back();
        }

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
        $filters = $this->communityFilters($request);
        $userId = $request->user()?->id;

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

        $posts = CommunityPost::query()
            ->with(['author', 'translations'])
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $search = $filters['search'];
                $query->where(function ($inner) use ($search): void {
                    $inner->where('content', 'like', "%{$search}%")
                        ->orWhereHas('author', fn ($author) => $author->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['type'] === 'official', fn ($query) => $query->where('is_official', true))
            ->when($filters['type'] === 'member', fn ($query) => $query->where('is_official', false))
            ->latest()
            ->paginate(self::COMMUNITY_POSTS_PER_PAGE)
            ->withQueryString()
            ->through(fn (CommunityPost $post) => $this->communityPostRow($post, $userId));

        return Inertia::render('admin/community/index', [
            'items' => $items,
            'posts' => $posts,
            'filters' => $filters,
            'locales' => config('maison.locales'),
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

    public function resolveCommunityReport(
        ResolveCommunityReportRequest $request,
        string $locale,
        CommunityReport $communityReport,
    ): RedirectResponse {
        $communityReport->update([
            'status' => $request->validated('status'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Melding bijgewerkt.')]);

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

    public function circle(Request $request, string $locale, PassportPresenter $passport): Response
    {
        $members = User::query()
            ->role(RoleEnum::FOUNDING_CIRCLE->value)
            ->get()
            ->map(fn (User $user) => $this->circleMemberPayload($user, $passport));

        return Inertia::render('admin/circle/index', [
            'members' => $members,
        ]);
    }

    public function circleShow(Request $request, string $locale, User $member, PassportPresenter $passport): Response
    {
        return Inertia::render('admin/circle/show', [
            'member' => [
                ...$this->circleMemberPayload($member, $passport),
                'benefits' => [__('Digital Heritage Passport'), __('Founding Circle Card')],
            ],
        ]);
    }

    public function assignCircleMember(
        AssignCircleMemberRequest $request,
        string $locale,
    ): RedirectResponse {
        $user = $this->resolveCircleUser($request);

        $user->assignRole(RoleEnum::FOUNDING_CIRCLE->value);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Lid toegevoegd aan Founding Circle.')]);

        return back();
    }

    public function removeCircleMember(Request $request, string $locale, User $member): RedirectResponse
    {
        $member->removeRole(RoleEnum::FOUNDING_CIRCLE->value);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Lid verwijderd uit Founding Circle.')]);

        return back();
    }

    public function heritage(Request $request, string $locale, EditionInventory $inventory, ProductController $products): Response
    {
        $id = $request->integer('product');
        $product = $id > 0
            ? Product::query()->where('type', ProductType::LimitedEdition)->find($id)
            : Product::founding();

        $product ??= Product::founding();

        abort_if($product === null, 404);

        return $products->renderInventory($request, $product, $inventory);
    }

    public function updateHeritageProduct(
        UpdateHeritageProductRequest $request,
        string $locale,
        Product $product,
    ): RedirectResponse {
        $product->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Editieproduct bijgewerkt.'),
        ]);

        return back();
    }

    /**
     * @return array{id: string, name: string, email: string, edition: string|null, status: string, joined_at: string|null}
     */
    private function circleMemberPayload(User $user, PassportPresenter $passport): array
    {
        $order = $passport->heritageOrder($user);

        return [
            'id' => (string) $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'edition' => $order?->edition_number !== null
                ? str_pad((string) $order->edition_number, 3, '0', STR_PAD_LEFT)
                : null,
            'status' => $order !== null ? 'Active' : 'Reserved',
            'joined_at' => $order?->created_at?->toDateString(),
        ];
    }

    private function resolveCircleUser(AssignCircleMemberRequest $request): User
    {
        $data = $request->validated();

        if (! empty($data['user_id'])) {
            return User::query()->findOrFail($data['user_id']);
        }

        return User::query()->where('email', $data['email'])->firstOrFail();
    }

    /**
     * @return array{search: string, status: string, type: string}
     */
    private function communityFilters(Request $request): array
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        $type = trim((string) $request->query('type', ''));

        if (! in_array($status, ['published', 'hidden'], true)) {
            $status = '';
        }

        if (! in_array($type, ['official', 'member'], true)) {
            $type = '';
        }

        return [
            'search' => $search,
            'status' => $status,
            'type' => $type,
        ];
    }

    /**
     * @return array{
     *     id: string,
     *     author: string,
     *     author_id: string,
     *     content: string,
     *     excerpt: string,
     *     is_truncated: bool,
     *     is_official: bool,
     *     status: string,
     *     can_edit: bool,
     *     created_at: string|null,
     *     translations: array<string, array{content: string}>,
     *     translationStatus: array<string, array{content: bool}>
     * }
     */
    private function communityPostRow(CommunityPost $post, ?int $userId): array
    {
        $sourceContent = $post->content;
        $displayContent = $post->translated('content');

        return [
            'id' => (string) $post->id,
            'author' => $post->author->name,
            'author_id' => (string) $post->author_id,
            'content' => $sourceContent,
            'excerpt' => CommunityPostPresenter::excerpt($displayContent),
            'is_truncated' => CommunityPostPresenter::isTruncated($displayContent),
            'is_official' => $post->is_official,
            'status' => $post->status,
            'can_edit' => $userId !== null && $post->author_id === $userId,
            'created_at' => $post->created_at?->toIso8601String(),
            'translations' => $this->communityPostTranslationBundle($post),
            'translationStatus' => $this->communityPostTranslationStatus($post),
        ];
    }

    /**
     * @return array<string, array{content: string}>
     */
    private function communityPostTranslationBundle(CommunityPost $post): array
    {
        $bundle = [];

        foreach (config('maison.locales') as $targetLocale) {
            $bundle[$targetLocale] = [
                'content' => $post->translated('content', $targetLocale),
            ];
        }

        return $bundle;
    }

    /**
     * @return array<string, array{content: bool}>
     */
    private function communityPostTranslationStatus(CommunityPost $post): array
    {
        $post->loadMissing('translations');

        $status = [];

        foreach (config('maison.locales') as $targetLocale) {
            $status[$targetLocale] = [
                'content' => $post->translations->contains(
                    fn ($translation): bool => $translation->locale === $targetLocale
                        && $translation->column === 'content',
                ),
            ];
        }

        return $status;
    }
}
