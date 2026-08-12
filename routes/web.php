<?php

use App\Enums\PermissionEnum;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\FileUploadDemoController;
use App\Http\Controllers\MaisonController;
use App\Http\Controllers\Member\DashboardController;
use App\Http\Controllers\PostAttachmentController;
use App\Http\Controllers\SitemapController;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;

/*
 * The public site is locale-prefixed, so the bare root sends visitors to the
 * Dutch source language. `home` stays the canonical name for the site root.
 */
Route::redirect('/', '/'.config('maison.default_locale'))->name('home');

Route::get('sitemap.xml', SitemapController::class)->name('sitemap');

Route::pattern('locale', implode('|', config('maison.locales')));

/*
 * The 14 public pages. Slugs are canonical rather than translated, so the same
 * path resolves under every locale prefix.
 */
Route::prefix('{locale}')
    ->middleware('locale')
    ->name('maison.')
    ->controller(MaisonController::class)
    ->group(function () {
        Route::get('/', 'home')->name('home');
        Route::get('huis', 'house')->name('house');
        Route::get('product', 'product')->name('product');
        Route::get('story', 'story')->name('story');
        Route::get('circle', 'circle')->name('circle');
        Route::get('dressing', 'dressing')->name('dressing');
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

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::prefix('member')
        ->name('member.')
        ->controller(DashboardController::class)
        ->group(function () {
            Route::get('/', 'index')->name('dashboard');
            Route::get('heritage', 'heritage')->name('heritage');
            Route::get('orders', 'orders')->name('orders');
            Route::get('passport', 'passport')->name('passport');
            Route::get('circle', 'circle')->name('circle');
            Route::get('letter', 'letter')->name('letter');
            Route::get('profile', 'profile')->name('profile');
            Route::patch('profile', 'updateProfile')->name('profile.update');
            Route::get('security', 'security')->name('security');
            Route::delete('profile', 'destroy')->name('profile.destroy');
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
        // Users — full CRUD with Precognition on write routes.
        Route::controller(UserController::class)->group(function () {
            Route::get('users', 'index')->name('users.index')
                ->middleware('permission:'.PermissionEnum::USERS_INDEX->value);
            Route::get('users/create', 'create')->name('users.create')
                ->middleware('permission:'.PermissionEnum::USERS_CREATE->value);
            Route::post('users', 'store')->name('users.store')
                ->middleware(['permission:'.PermissionEnum::USERS_CREATE->value, HandlePrecognitiveRequests::class]);
            Route::get('users/{user}', 'show')->name('users.show')
                ->middleware('permission:'.PermissionEnum::USERS_VIEW->value);
            Route::get('users/{user}/edit', 'edit')->name('users.edit')
                ->middleware('permission:'.PermissionEnum::USERS_EDIT->value);
            Route::put('users/{user}', 'update')->name('users.update')
                ->middleware(['permission:'.PermissionEnum::USERS_EDIT->value, HandlePrecognitiveRequests::class]);
            Route::delete('users/{user}', 'destroy')->name('users.destroy')
                ->middleware('permission:'.PermissionEnum::USERS_DELETE->value);
        });

        // Roles — full CRUD with grouped permission assignment.
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

        // Permissions — read-only listing + CSV / Excel export.
        Route::controller(PermissionController::class)->group(function () {
            Route::get('permissions', 'index')->name('permissions.index')
                ->middleware('permission:'.PermissionEnum::PERMISSIONS_INDEX->value);
            Route::get('permissions/export', 'export')->name('permissions.export')
                ->middleware('permission:'.PermissionEnum::PERMISSIONS_EXPORT->value);
        });
    });
});

require __DIR__.'/settings.php';
