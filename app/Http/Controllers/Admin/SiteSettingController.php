<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSiteSettingRequest;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SiteSettingController extends Controller
{
    public function edit(Request $request, string $locale): Response
    {
        $settings = SiteSetting::current();

        return Inertia::render('admin/site-settings/edit', [
            'settings' => [
                'phone' => $settings->phone,
                'whatsapp' => $settings->whatsapp,
                'email_hello' => $settings->email_hello,
                'email_press' => $settings->email_press,
                'instagram_url' => $settings->instagram_url,
                'boutique_lat' => (string) $settings->boutique_lat,
                'boutique_lng' => (string) $settings->boutique_lng,
                'announcement_text' => $settings->announcement_text,
            ],
        ]);
    }

    public function update(UpdateSiteSettingRequest $request, string $locale): RedirectResponse
    {
        SiteSetting::current()->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Site-instellingen opgeslagen.')]);

        return back();
    }
}
