<?php

namespace App\Http\Controllers\Member;

use App\Enums\RegisterVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Member\UpdateRegisterListingRequest;
use App\Services\FoundingCircle\FoundingCircleRegistrar;
use App\Support\FoundingCircleRegisterPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegisterListingController extends Controller
{
    public function edit(Request $request, string $locale, FoundingCircleRegisterPresenter $presenter): Response
    {
        return Inertia::render('member/register-listing', [
            'listing' => $presenter->listingPreview($request->user()),
        ]);
    }

    public function update(
        UpdateRegisterListingRequest $request,
        string $locale,
        FoundingCircleRegistrar $registrar,
    ): RedirectResponse {
        $registrar->updateListing(
            $request->user(),
            RegisterVisibility::from($request->validated('visibility')),
            $request->boolean('consent'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Wijzigingen verschijnen meteen in het register.')]);

        return back();
    }
}
