<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductType;
use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignCircleMemberRequest;
use App\Http\Requests\Admin\ResolveCommunityReportRequest;
use App\Http\Requests\Admin\UpdateHeritageProductRequest;
use App\Models\CommunityPost;
use App\Models\CommunityReport;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Checkout\OrderStatusService;
use App\Services\Edition\EditionInventory;
use App\Support\CommunityPostPresenter;
use App\Support\OrderPresenter;
use App\Support\PassportPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\Exception\ApiErrorException;

class OpsController extends Controller
{
    private const COMMUNITY_POSTS_PER_PAGE = 15;

    private const ORDERS_PER_PAGE_DEFAULT = 15;

    /** @var list<int> */
    private const ORDERS_PER_PAGE_OPTIONS = [10, 15, 25, 50, 100];

    public function orders(Request $request, string $locale, OrderPresenter $presenter): Response
    {
        $filters = $this->orderFilters($request);

        $orders = Order::query()
            ->with(['product', 'user:id,name,email', 'latestPayment'])
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $search = $filters['search'];
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('product', fn ($product) => $product->where('name', 'like', "%{$search}%"));

                    if (ctype_digit($search)) {
                        $inner->orWhere('id', (int) $search);
                    } elseif (preg_match('/(?:MA-)?\d{0,4}-?0*(\d+)$/i', $search, $matches) === 1) {
                        $inner->orWhere('id', (int) $matches[1]);
                    }
                });
            })
            ->when(
                $filters['status'] !== null,
                fn ($query) => $query->where('status', $filters['status']),
            )
            ->when(
                $filters['payment_status'] !== null,
                fn ($query) => $query->whereHas(
                    'latestPayment',
                    fn ($payment) => $payment->where('status', $filters['payment_status']),
                ),
            )
            ->latest()
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->through(function (Order $order) use ($presenter): array {
                $summary = $presenter->summary($order);
                $payment = $order->latestPayment;

                return [
                    ...$summary,
                    'product_name' => $order->product?->translated('name') ?? __('Product'),
                    'customer' => $order->name,
                    'customer_email' => $order->email,
                    'user_id' => $order->user_id,
                    'payment_status' => $payment !== null
                        ? $presenter->paymentStatusLabel($payment->status)
                        : null,
                    'payment_status_key' => $payment?->status->value,
                ];
            });

        return Inertia::render('admin/orders/index', [
            'orders' => $orders,
            'filters' => [
                'search' => $filters['search'],
                'status' => $filters['status']?->value ?? '',
                'payment_status' => $filters['payment_status']?->value ?? '',
                'per_page' => $filters['per_page'],
            ],
            'statusOptions' => collect(OrderStatus::cases())
                ->map(fn (OrderStatus $status) => [
                    'value' => $status->value,
                    'label' => $presenter->statusLabel($status),
                ])
                ->values()
                ->all(),
            'paymentStatusOptions' => collect(PaymentStatus::cases())
                ->map(fn (PaymentStatus $status) => [
                    'value' => $status->value,
                    'label' => $presenter->paymentStatusLabel($status),
                ])
                ->values()
                ->all(),
            'perPageOptions' => self::ORDERS_PER_PAGE_OPTIONS,
        ]);
    }

    /**
     * @return array{search: string, status: OrderStatus|null, payment_status: PaymentStatus|null, per_page: int}
     */
    private function orderFilters(Request $request): array
    {
        $search = trim((string) $request->query('search', ''));
        $statusValue = trim((string) $request->query('status', ''));
        $paymentStatusValue = trim((string) $request->query('payment_status', ''));
        $perPage = (int) $request->query('per_page', self::ORDERS_PER_PAGE_DEFAULT);

        if (! in_array($perPage, self::ORDERS_PER_PAGE_OPTIONS, true)) {
            $perPage = self::ORDERS_PER_PAGE_DEFAULT;
        }

        return [
            'search' => $search,
            'status' => OrderStatus::tryFrom($statusValue),
            'payment_status' => PaymentStatus::tryFrom($paymentStatusValue),
            'per_page' => $perPage,
        ];
    }

    public function orderShow(Request $request, string $locale, Order $order, OrderPresenter $presenter): Response
    {
        $detail = $presenter->detail($order);
        $detail['customer'] = $order->name;
        $detail['user_id'] = $order->user_id;

        return Inertia::render('admin/orders/show', [
            'order' => $detail,
        ]);
    }

    public function updateOrderStatus(
        Request $request,
        string $locale,
        Order $order,
        OrderStatusService $statusService,
    ): RedirectResponse {
        $data = $request->validate([
            'status' => ['required', 'in:processing,shipped,delivered,refunded,canceled'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $status = OrderStatus::from($data['status']);

        try {
            $statusService->transition(
                $order,
                $status,
                $data['message'],
                $request->user(),
            );
        } catch (ApiErrorException) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Stripe-terugbetaling mislukt. Probeer opnieuw.'),
            ]);

            return back();
        } catch (ValidationException $exception) {
            throw $exception;
        }

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
                'benefits' => [__('Digitaal Heritage Passport'), __('Founding Circle-kaart')],
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
