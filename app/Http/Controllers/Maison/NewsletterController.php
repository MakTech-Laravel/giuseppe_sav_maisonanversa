<?php

namespace App\Http\Controllers\Maison;

use App\Http\Controllers\Controller;
use App\Http\Requests\Maison\NewsletterSubscribeRequest;
use App\Models\NewsletterSubscriber;
use App\Services\Newsletter\HeritageLetterSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NewsletterController extends Controller
{
    public function store(
        NewsletterSubscribeRequest $request,
        string $locale,
        HeritageLetterSubscription $subscription,
    ): RedirectResponse {
        $subscription->subscribe($request->validated(), $request, $locale);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Welkom bij de Heritage Letter.'),
        ]);

        return back();
    }

    public function unsubscribe(
        Request $request,
        string $locale,
        NewsletterSubscriber $subscriber,
        HeritageLetterSubscription $subscription,
    ): Response {
        abort_unless($request->hasValidSignature(), 403);

        $subscription->unsubscribe($subscriber);

        return Inertia::render('maison/newsletter-unsubscribed');
    }
}
