<?php

use App\Enums\PermissionEnum;
use App\Http\Controllers\Admin\CommerceSettingController;
use App\Http\Controllers\Admin\CommunityCourtController;
use App\Http\Controllers\Admin\CommunityEventController;
use App\Http\Controllers\Admin\CommunityPostController;
use App\Http\Controllers\Admin\CommunitySessionController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DressingItemController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\JournalArticleController;
use App\Http\Controllers\Admin\LegalPageController;
use App\Http\Controllers\Admin\OpsController;
use App\Http\Controllers\Admin\PartnerClubController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SeoMetaController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthModalRedirectController;
use App\Http\Controllers\Community\CommunityController;
use App\Http\Controllers\CookieConsentController;
use App\Http\Controllers\FileUploadDemoController;
use App\Http\Controllers\Maison\CheckoutController;
use App\Http\Controllers\Maison\InquiryController;
use App\Http\Controllers\Maison\NewsletterController;
use App\Http\Controllers\Maison\VerificationController;
use App\Http\Controllers\MaisonController;
use App\Http\Controllers\Member\DashboardController;
use App\Http\Controllers\Member\NotificationController;
use App\Http\Controllers\Member\PassportPdfController;
use App\Http\Controllers\PostAttachmentController;
use App\Http\Controllers\RobotsTxtController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\SitemapController;
use App\Services\Auth\PostLoginRedirectService;
use App\Services\Locale\LocalePreferenceService;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
 * The public site is locale-prefixed. Bare `/` negotiates cookie → session →
 * Accept-Language → default so returning visitors keep their language choice.
 */

Route::get('/', function (Request $request, LocalePreferenceService $locales) {
    $locale = $locales->preferred($request);
    $locales->remember($request, $locale);

    return redirect('/'.$locale);
})->name('home');

Route::get('robots.txt', RobotsTxtController::class)->name('robots');
Route::get('sitemap.xml', SitemapController::class)->name('sitemap');

Route::post('cookie-consent', [CookieConsentController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('cookie-consent.store');

Route::middleware('web')->controller(AuthModalRedirectController::class)->group(function () {
    Route::get('login', 'login')->name('login');
    Route::get('register', 'register')->name('register');
    Route::get('forgot-password', 'forgotPassword')->name('password.request');
    Route::get('two-factor-challenge', 'twoFactor')->name('two-factor.login');
});

Route::pattern('locale', implode('|', config('maison.locales')));

/*
 * The 14 public pages. Slugs are canonical rather than translated, so the same
 * path resolves under every locale prefix.
 */
Route::prefix('{locale}')
    ->middleware('locale')
    ->name('maison.')
    ->group(function () {
        Route::controller(MaisonController::class)->group(function () {
            Route::get('/', 'home')->name('home');
            Route::get('huis', 'house')->name('house');
            Route::get('product', 'product')->name('product');
            Route::get('story', 'story')->name('story');
            Route::get('circle', 'circle')->name('circle');
            Route::get('dressing', 'dressing')->name('dressing');
            Route::get('dressing/{dressingItem:slug}', 'dressingShow')->name('dressing.show');
            Route::get('journal', 'journal')->name('journal');
            Route::get('journal/{slug}', 'journalShow')
                ->where('slug', '[a-z0-9-]+')
                ->name('journal.show');
            Route::get('community', 'community')->name('community');
            Route::get('corner', 'corner')->name('corner');
            Route::get('contact', 'contact')->name('contact');
            Route::get('privacy', 'privacy')->name('privacy');
            Route::get('terms', 'terms')->name('terms');
            Route::get('shipping', 'shipping')->name('shipping');
            Route::get('care', 'care')->name('care');
        });

        Route::controller(InquiryController::class)->group(function () {
            Route::post('contact', 'storeContact')
                ->middleware('throttle:5,1')
                ->name('contact.store');
            Route::post('corner/inquire', 'storeCorner')
                ->middleware('throttle:5,1')
                ->name('corner.inquire');
        });

        Route::get('verify/{token}', VerificationController::class)
            ->where('token', '[0-9a-fA-F-]{36}')
            ->name('verify');

        Route::post('heritage-letter', [NewsletterController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('heritage-letter.store');
        Route::get('heritage-letter/unsubscribe/{subscriber}', [NewsletterController::class, 'unsubscribe'])
            ->middleware('signed')
            ->name('heritage-letter.unsubscribe');

        Route::controller(CheckoutController::class)->group(function () {
            Route::post('checkout', 'store')
                ->middleware('throttle:10,1')
                ->name('checkout.store');
            Route::get('checkout/success', 'success')->name('checkout.success');
            Route::get('checkout/cancel', 'cancel')->name('checkout.cancel');
        });
    });

/*
 * Auth pages that need a locale prefix (password reset, verify, confirm).
 * Public Maison pages live in the group above.
 */
Route::prefix('{locale}')
    ->middleware('locale')
    ->group(function () {
        Route::get('reset-password/{token}', fn (Request $request, string $token) => Inertia::render('auth/reset-password', [
            'email' => $request->email,
            'token' => $token,
        ]))->middleware('guest')->name('password.reset');

        Route::get('email/verify', function (Request $request) {
            if ($request->user()?->hasVerifiedEmail()) {
                $home = app(PostLoginRedirectService::class)
                    ->urlFor($request->user(), $request);

                return redirect()->intended($home);
            }

            return Inertia::render('auth/verify-email', [
                'status' => $request->session()->get('status'),
            ]);
        })->middleware('auth')->name('verification.notice');

        Route::get('user/confirm-password', function (Request $request) {
            $intended = $request->session()->get('url.intended', '');

            if (is_string($intended) && str_contains($intended, '/member')) {
                return Inertia::render('member/confirm-password');
            }

            return Inertia::render('auth/confirm-password');
        })
            ->middleware('auth')
            ->name('password.confirm');
    });

Route::prefix('{locale}')
    ->middleware(['locale', 'auth', 'verified'])
    ->group(function () {
        Route::get('dashboard', function (string $locale) {
            return redirect()->route('admin.dashboard', ['locale' => $locale]);
        });

        Route::prefix('member')
            ->name('member.')
            ->controller(DashboardController::class)
            ->group(function () {
                Route::get('/', 'index')->name('dashboard');
                Route::get('heritage', 'heritage')->name('heritage');
                Route::get('orders', 'orders')->name('orders');
                Route::get('orders/{order}', 'orderShow')->name('orders.show');
                Route::get('passport', 'passport')->name('passport');
                Route::get('passport.pdf', PassportPdfController::class)->name('passport.pdf');
                Route::get('circle', 'circle')->name('circle');
                Route::get('letter', 'letter')->name('letter');
                Route::patch('letter', 'updateLetter')->name('letter.update');
                Route::get('profile', 'profile')->name('profile');
                Route::patch('profile', 'updateProfile')->name('profile.update');
                Route::get('security', 'security')->name('security');
                Route::delete('profile', 'destroy')->name('profile.destroy');
                Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
                Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
            });

        Route::prefix('community')->name('community.')->controller(CommunityController::class)->group(function () {
            Route::post('posts', 'storePost')->name('posts.store');
            Route::post('posts/{communityPost}/comments', 'storeComment')->name('posts.comments.store');
            Route::post('posts/{communityPost}/like', 'toggleLike')->name('posts.like');
            Route::post('posts/{communityPost}/hide', 'hidePost')->name('posts.hide');
            Route::post('posts/{communityPost}/report', 'reportPost')->name('posts.report');
            Route::post('sessions', 'storeSession')->name('sessions.store');
            Route::post('sessions/{communitySession}/join', 'joinSession')->name('sessions.join');
            Route::delete('sessions/{communitySession}/leave', 'leaveSession')->name('sessions.leave');
            Route::post('events/{communityEvent}/rsvp', 'rsvpEvent')->name('events.rsvp');
            Route::delete('events/{communityEvent}/rsvp', 'cancelRsvp')->name('events.rsvp.cancel');
        });

        // ── Demo landing page ─────────────────────────────────────────────────────
        Route::get('/file-upload-demo', [FileUploadDemoController::class, 'index'])
            ->name('file-upload-demo.index')->middleware('permission:'.PermissionEnum::FILE_UPLOAD_INDEX->value);

        // ── Single / multiple file upload (used by demos 1 & 2) ──────────────────
        Route::post('/upload', [FileUploadDemoController::class, 'store'])
            ->name('upload.store')->middleware('permission:'.PermissionEnum::FILE_UPLOAD_STORE->value);

        // ── Edit-mode endpoint (demo 3) ───────────────────────────────────────────
        Route::post('/posts/{post}', [PostAttachmentController::class, 'update'])
            ->name('posts.update')->middleware('permission:'.PermissionEnum::POSTS_EDIT->value);

        // ── Admin: access management ──────────────────────────────────────────────
        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('dashboard', AdminDashboardController::class)
                ->name('dashboard')
                ->middleware('permission:'.PermissionEnum::DASHBOARD_VIEW->value);

            Route::controller(CustomerController::class)->group(function () {
                Route::get('customers', 'index')->name('customers.index')
                    ->middleware('permission:'.PermissionEnum::USERS_INDEX->value);
                Route::get('customers/create', 'create')->name('customers.create')
                    ->middleware('permission:'.PermissionEnum::USERS_CREATE->value);
                Route::post('customers', 'store')->name('customers.store')
                    ->middleware(['permission:'.PermissionEnum::USERS_CREATE->value, HandlePrecognitiveRequests::class]);
                Route::get('customers/{user}', 'show')->name('customers.show')
                    ->middleware('permission:'.PermissionEnum::USERS_VIEW->value);
                Route::get('customers/{user}/edit', 'edit')->name('customers.edit')
                    ->middleware('permission:'.PermissionEnum::USERS_EDIT->value);
                Route::put('customers/{user}', 'update')->name('customers.update')
                    ->middleware(['permission:'.PermissionEnum::USERS_EDIT->value, HandlePrecognitiveRequests::class]);
                Route::delete('customers/{user}', 'destroy')->name('customers.destroy')
                    ->middleware('permission:'.PermissionEnum::USERS_DELETE->value);
            });

            Route::controller(OpsController::class)->group(function () {
                Route::get('orders', 'orders')->name('orders.index')
                    ->middleware('permission:'.PermissionEnum::ORDERS_MANAGE->value);
                Route::get('orders/{order}', 'orderShow')->name('orders.show')
                    ->middleware('permission:'.PermissionEnum::ORDERS_MANAGE->value);
                Route::patch('orders/{order}', 'updateOrderStatus')->name('orders.update')
                    ->middleware('permission:'.PermissionEnum::ORDERS_MANAGE->value);
                Route::get('circle', 'circle')->name('circle.index')
                    ->middleware('permission:'.PermissionEnum::DASHBOARD_VIEW->value);
                Route::post('circle', 'assignCircleMember')->name('circle.assign')
                    ->middleware('permission:'.PermissionEnum::DASHBOARD_VIEW->value);
                Route::get('circle/{member}', 'circleShow')->name('circle.show')
                    ->middleware('permission:'.PermissionEnum::DASHBOARD_VIEW->value);
                Route::delete('circle/{member}', 'removeCircleMember')->name('circle.remove')
                    ->middleware('permission:'.PermissionEnum::DASHBOARD_VIEW->value);
                Route::get('heritage', 'heritage')->name('heritage.index')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
                Route::patch('heritage/{product}', 'updateHeritageProduct')->name('heritage.update')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);

                Route::get('community', 'community')->name('community.index')
                    ->middleware('permission:'.PermissionEnum::COMMUNITY_MODERATE->value);
                Route::post('community/official', 'storeOfficialPost')->name('community.official')
                    ->middleware('permission:'.PermissionEnum::COMMUNITY_OFFICIAL->value);
                Route::post('community/posts/{communityPost}/hide', 'hideCommunityPost')->name('community.hide')
                    ->middleware('permission:'.PermissionEnum::COMMUNITY_MODERATE->value);
                Route::patch('community/reports/{communityReport}', 'resolveCommunityReport')->name('community.reports.resolve')
                    ->middleware('permission:'.PermissionEnum::COMMUNITY_MODERATE->value);
                Route::get('letter', 'letter')->name('letter.index')
                    ->middleware('permission:'.PermissionEnum::DASHBOARD_VIEW->value);
                Route::get('letter/export', 'exportLetter')->name('letter.export')
                    ->middleware('permission:'.PermissionEnum::DASHBOARD_VIEW->value);
            });

            Route::controller(CommunityPostController::class)->group(function () {
                Route::put('community/posts/{communityPost}', 'update')->name('community.posts.update')
                    ->middleware('permission:'.PermissionEnum::COMMUNITY_MODERATE->value);
                Route::put('community/posts/{communityPost}/translations', 'updateTranslations')->name('community.posts.translations.update')
                    ->middleware('permission:'.PermissionEnum::COMMUNITY_MODERATE->value);
                Route::post('community/posts/{communityPost}/translate', 'translate')->name('community.posts.translate')
                    ->middleware('permission:'.PermissionEnum::COMMUNITY_MODERATE->value);
            });

            Route::controller(CommunityEventController::class)->group(function () {
                Route::get('events', 'index')->name('events.index')
                    ->middleware('permission:'.PermissionEnum::DASHBOARD_VIEW->value);
                Route::get('events/create', 'create')->name('events.create')
                    ->middleware('permission:'.PermissionEnum::DASHBOARD_VIEW->value);
                Route::post('events', 'store')->name('events.store')
                    ->middleware('permission:'.PermissionEnum::DASHBOARD_VIEW->value);
                Route::get('events/{event}', 'show')->name('events.show')
                    ->middleware('permission:'.PermissionEnum::DASHBOARD_VIEW->value);
                Route::get('events/{event}/edit', 'edit')->name('events.edit')
                    ->middleware('permission:'.PermissionEnum::DASHBOARD_VIEW->value);
                Route::put('events/{event}', 'update')->name('events.update')
                    ->middleware('permission:'.PermissionEnum::DASHBOARD_VIEW->value);
                Route::patch('events/{event}/status', 'updateStatus')->name('events.status')
                    ->middleware('permission:'.PermissionEnum::DASHBOARD_VIEW->value);
                Route::put('events/{event}/translations', 'updateTranslations')->name('events.translations.update')
                    ->middleware('permission:'.PermissionEnum::DASHBOARD_VIEW->value);
                Route::post('events/{event}/translate', 'translate')->name('events.translate')
                    ->middleware('permission:'.PermissionEnum::DASHBOARD_VIEW->value);
                Route::delete('events/{event}', 'destroy')->name('events.destroy')
                    ->middleware('permission:'.PermissionEnum::DASHBOARD_VIEW->value);
            });

            Route::controller(CommunityCourtController::class)->group(function () {
                Route::get('courts', 'index')->name('courts.index')
                    ->middleware('permission:'.PermissionEnum::COMMUNITY_MODERATE->value);
                Route::get('courts/create', 'create')->name('courts.create')
                    ->middleware('permission:'.PermissionEnum::COMMUNITY_MODERATE->value);
                Route::post('courts', 'store')->name('courts.store')
                    ->middleware('permission:'.PermissionEnum::COMMUNITY_MODERATE->value);
                Route::get('courts/{court}', 'show')->name('courts.show')
                    ->middleware('permission:'.PermissionEnum::COMMUNITY_MODERATE->value);
                Route::get('courts/{court}/edit', 'edit')->name('courts.edit')
                    ->middleware('permission:'.PermissionEnum::COMMUNITY_MODERATE->value);
                Route::put('courts/{court}', 'update')->name('courts.update')
                    ->middleware('permission:'.PermissionEnum::COMMUNITY_MODERATE->value);
                Route::put('courts/{court}/translations', 'updateTranslations')->name('courts.translations.update')
                    ->middleware('permission:'.PermissionEnum::COMMUNITY_MODERATE->value);
                Route::post('courts/{court}/translate', 'translate')->name('courts.translate')
                    ->middleware('permission:'.PermissionEnum::COMMUNITY_MODERATE->value);
                Route::delete('courts/{court}', 'destroy')->name('courts.destroy')
                    ->middleware('permission:'.PermissionEnum::COMMUNITY_MODERATE->value);
            });

            Route::controller(JournalArticleController::class)->group(function () {
                Route::get('journal', 'index')->name('journal.index')
                    ->middleware('permission:'.PermissionEnum::POSTS_VIEW->value);
                Route::get('journal/create', 'create')->name('journal.create')
                    ->middleware('permission:'.PermissionEnum::POSTS_CREATE->value);
                Route::post('journal', 'store')->name('journal.store')
                    ->middleware('permission:'.PermissionEnum::POSTS_CREATE->value);
                Route::get('journal/{article}', 'show')->name('journal.show')
                    ->middleware('permission:'.PermissionEnum::POSTS_VIEW->value);
                Route::get('journal/{article}/edit', 'edit')->name('journal.edit')
                    ->middleware('permission:'.PermissionEnum::POSTS_EDIT->value);
                Route::put('journal/{article}', 'update')->name('journal.update')
                    ->middleware('permission:'.PermissionEnum::POSTS_EDIT->value);
                Route::delete('journal/{article}', 'destroy')->name('journal.destroy')
                    ->middleware('permission:'.PermissionEnum::POSTS_DELETE->value);
            });

            Route::controller(AdminProductController::class)->group(function () {
                Route::get('products', 'index')->name('products.index')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
                Route::get('products/create', 'create')->name('products.create')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
                Route::post('products', 'store')->name('products.store')
                    ->middleware(['permission:'.PermissionEnum::HERITAGE_VIEW->value, HandlePrecognitiveRequests::class]);
                Route::get('products/{product}', 'show')->name('products.show')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
                Route::get('products/{product}/edit', 'edit')->name('products.edit')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
                Route::put('products/{product}', 'update')->name('products.update')
                    ->middleware(['permission:'.PermissionEnum::HERITAGE_VIEW->value, HandlePrecognitiveRequests::class]);
                Route::put('products/{product}/translations', 'updateTranslations')->name('products.translations.update')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
                Route::post('products/{product}/translate', 'translate')->name('products.translate')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
                Route::delete('products/{product}', 'destroy')->name('products.destroy')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
                Route::get('products/{product}/inventory', 'inventory')->name('products.inventory')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
            });

            Route::controller(CommerceSettingController::class)->group(function () {
                Route::get('commerce', 'edit')->name('commerce.edit')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
                Route::patch('commerce', 'update')->name('commerce.update')
                    ->middleware(['permission:'.PermissionEnum::HERITAGE_VIEW->value, HandlePrecognitiveRequests::class]);
            });

            Route::controller(FaqController::class)->group(function () {
                Route::get('faqs', 'index')->name('faqs.index')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
                Route::get('faqs/create', 'create')->name('faqs.create')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
                Route::post('faqs', 'store')->name('faqs.store')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
                Route::get('faqs/{faq}', 'show')->name('faqs.show')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
                Route::get('faqs/{faq}/edit', 'edit')->name('faqs.edit')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
                Route::put('faqs/{faq}', 'update')->name('faqs.update')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
                Route::put('faqs/{faq}/translations', 'updateTranslations')->name('faqs.translations.update')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
                Route::post('faqs/{faq}/translate', 'translate')->name('faqs.translate')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
                Route::delete('faqs/{faq}', 'destroy')->name('faqs.destroy')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
            });

            Route::controller(DressingItemController::class)->group(function () {
                Route::get('dressing-items', 'index')->name('dressing-items.index')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
                Route::get('dressing-items/create', 'create')->name('dressing-items.create')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
                Route::post('dressing-items', 'store')->name('dressing-items.store')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
                Route::get('dressing-items/{dressingItem}', 'show')->name('dressing-items.show')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
                Route::get('dressing-items/{dressingItem}/edit', 'edit')->name('dressing-items.edit')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
                Route::put('dressing-items/{dressingItem}', 'update')->name('dressing-items.update')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
                Route::delete('dressing-items/{dressingItem}', 'destroy')->name('dressing-items.destroy')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
            });

            Route::controller(PartnerClubController::class)->group(function () {
                Route::get('partner-clubs', 'index')->name('partner-clubs.index')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
                Route::get('partner-clubs/create', 'create')->name('partner-clubs.create')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
                Route::post('partner-clubs', 'store')->name('partner-clubs.store')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
                Route::get('partner-clubs/{partnerClub}/edit', 'edit')->name('partner-clubs.edit')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
                Route::put('partner-clubs/{partnerClub}', 'update')->name('partner-clubs.update')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
                Route::delete('partner-clubs/{partnerClub}', 'destroy')->name('partner-clubs.destroy')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
            });

            Route::controller(SiteSettingController::class)->group(function () {
                Route::get('site-settings', 'edit')->name('site-settings.edit')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
                Route::patch('site-settings', 'update')->name('site-settings.update')
                    ->middleware(['permission:'.PermissionEnum::HERITAGE_VIEW->value, HandlePrecognitiveRequests::class]);
            });

            Route::controller(LegalPageController::class)->group(function () {
                Route::get('legal-pages', 'index')->name('legal-pages.index')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
                Route::get('legal-pages/{legalPage}/edit', 'edit')->name('legal-pages.edit')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
                Route::put('legal-pages/{legalPage}', 'update')->name('legal-pages.update')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
            });

            Route::controller(SeoMetaController::class)->group(function () {
                Route::get('seo-metas', 'index')->name('seo-metas.index')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
                Route::get('seo-metas/{seoMeta}/edit', 'edit')->name('seo-metas.edit')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
                Route::put('seo-metas/{seoMeta}', 'update')->name('seo-metas.update')
                    ->middleware('permission:'.PermissionEnum::HERITAGE_VIEW->value);
            });

            Route::controller(CommunitySessionController::class)->group(function () {
                Route::get('sessions', 'index')->name('community-sessions.index')
                    ->middleware('permission:'.PermissionEnum::SESSIONS_MANAGE->value);
                Route::get('sessions/create', 'create')->name('community-sessions.create')
                    ->middleware('permission:'.PermissionEnum::SESSIONS_MANAGE->value);
                Route::post('sessions', 'store')->name('community-sessions.store')
                    ->middleware('permission:'.PermissionEnum::SESSIONS_MANAGE->value);
                Route::get('sessions/{communitySession}/edit', 'edit')->name('community-sessions.edit')
                    ->middleware('permission:'.PermissionEnum::SESSIONS_MANAGE->value);
                Route::put('sessions/{communitySession}', 'update')->name('community-sessions.update')
                    ->middleware('permission:'.PermissionEnum::SESSIONS_MANAGE->value);
                Route::delete('sessions/{communitySession}', 'destroy')->name('community-sessions.destroy')
                    ->middleware('permission:'.PermissionEnum::SESSIONS_MANAGE->value);
            });

            Route::controller(AdminPostController::class)->group(function () {
                Route::get('posts', 'index')->name('posts.index')
                    ->middleware('permission:'.PermissionEnum::POSTS_VIEW->value);
                Route::get('posts/create', 'create')->name('posts.create')
                    ->middleware('permission:'.PermissionEnum::POSTS_CREATE->value);
                Route::post('posts', 'store')->name('posts.store')
                    ->middleware(['permission:'.PermissionEnum::POSTS_CREATE->value, HandlePrecognitiveRequests::class]);
                Route::get('posts/{post}', 'show')->name('posts.show')
                    ->middleware('permission:'.PermissionEnum::POSTS_VIEW->value);
                Route::get('posts/{post}/edit', 'edit')->name('posts.edit')
                    ->middleware('permission:'.PermissionEnum::POSTS_EDIT->value);
                Route::put('posts/{post}', 'update')->name('posts.update')
                    ->middleware(['permission:'.PermissionEnum::POSTS_EDIT->value, HandlePrecognitiveRequests::class]);
                Route::delete('posts/{post}', 'destroy')->name('posts.destroy')
                    ->middleware('permission:'.PermissionEnum::POSTS_DELETE->value);
            });

            // Admins — staff accounts (Access Control).
            Route::controller(UserController::class)->group(function () {
                Route::get('admins', 'index')->name('admins.index')
                    ->middleware('permission:'.PermissionEnum::USERS_INDEX->value);
                Route::get('admins/create', 'create')->name('admins.create')
                    ->middleware('permission:'.PermissionEnum::USERS_CREATE->value);
                Route::post('admins', 'store')->name('admins.store')
                    ->middleware(['permission:'.PermissionEnum::USERS_CREATE->value, HandlePrecognitiveRequests::class]);
                Route::get('admins/{user}', 'show')->name('admins.show')
                    ->middleware('permission:'.PermissionEnum::USERS_VIEW->value);
                Route::get('admins/{user}/edit', 'edit')->name('admins.edit')
                    ->middleware('permission:'.PermissionEnum::USERS_EDIT->value);
                Route::put('admins/{user}', 'update')->name('admins.update')
                    ->middleware(['permission:'.PermissionEnum::USERS_EDIT->value, HandlePrecognitiveRequests::class]);
                Route::delete('admins/{user}', 'destroy')->name('admins.destroy')
                    ->middleware('permission:'.PermissionEnum::USERS_DELETE->value);
            });

            Route::controller(RoleController::class)->group(function () {
                Route::get('roles', 'index')->name('roles.index')
                    ->middleware('permission:'.PermissionEnum::ROLES_INDEX->value);
                Route::get('roles/create', 'create')->name('roles.create')
                    ->middleware('permission:'.PermissionEnum::ROLES_CREATE->value);
                Route::post('roles', 'store')->name('roles.store')
                    ->middleware(['permission:'.PermissionEnum::ROLES_CREATE->value, HandlePrecognitiveRequests::class]);
                Route::get('roles/{role}/edit', 'edit')->name('roles.edit')
                    ->middleware('permission:'.PermissionEnum::ROLES_EDIT->value);
                Route::put('roles/{role}', 'update')->name('roles.update')
                    ->middleware(['permission:'.PermissionEnum::ROLES_EDIT->value, HandlePrecognitiveRequests::class]);
                Route::delete('roles/{role}', 'destroy')->name('roles.destroy')
                    ->middleware('permission:'.PermissionEnum::ROLES_DELETE->value);
            });

            Route::controller(PermissionController::class)->group(function () {
                Route::get('permissions', 'index')->name('permissions.index')
                    ->middleware('permission:'.PermissionEnum::PERMISSIONS_INDEX->value);
                Route::get('permissions/export', 'export')->name('permissions.export')
                    ->middleware('permission:'.PermissionEnum::PERMISSIONS_EXPORT->value);
            });
        });

        Route::middleware(['auth'])->group(function () {
            Route::redirect('settings', 'settings/profile');

            Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
            Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
        });

        Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
        Route::get('settings/security', [SecurityController::class, 'edit'])->name('security.edit');
        Route::put('settings/password', [SecurityController::class, 'update'])
            ->middleware('throttle:6,1')
            ->name('user-password.update');
    });

Route::prefix('{locale}')
    ->middleware('locale')
    ->group(function () {
        Route::fallback(function () {
            abort(404);
        });
    });
