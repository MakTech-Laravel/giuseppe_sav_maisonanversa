<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateStorefrontAccessRequest;
use App\Models\CommerceSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StorefrontAccessController extends Controller
{
    public function edit(Request $request, string $locale): Response
    {
        $settings = CommerceSetting::current();

        return Inertia::render('admin/storefront-access/edit', [
            'settings' => [
                'purchases_enabled' => $settings->purchases_enabled,
                'registration_enabled' => $settings->registration_enabled,
            ],
            'envPurchasesEnabled' => (bool) config('maison.checkout.enabled'),
            'envRegistrationEnabled' => (bool) config('maison.registration.enabled'),
        ]);
    }

    public function update(UpdateStorefrontAccessRequest $request, string $locale): RedirectResponse
    {
        CommerceSetting::current()->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Storefront access saved.'),
        ]);

        return back();
    }
}
