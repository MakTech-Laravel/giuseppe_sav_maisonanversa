<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCommerceSettingRequest;
use App\Models\CommerceSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommerceSettingController extends Controller
{
    public function edit(Request $request, string $locale): Response
    {
        $settings = CommerceSetting::current();

        return Inertia::render('admin/commerce/edit', [
            'settings' => [
                'shipping_estimate_min' => (string) $settings->shipping_estimate_min,
                'shipping_estimate_max' => (string) $settings->shipping_estimate_max,
                'shipping_eu_included' => $settings->shipping_eu_included,
                'default_expected_delivery_label' => $settings->default_expected_delivery_label,
                'prices_include_tax' => $settings->prices_include_tax,
            ],
        ]);
    }

    public function update(UpdateCommerceSettingRequest $request, string $locale): RedirectResponse
    {
        CommerceSetting::current()->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Handelsinstellingen opgeslagen.')]);

        return back();
    }
}
