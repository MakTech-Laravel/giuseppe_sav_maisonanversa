@component('emails.layouts.maison', [
    'title' => __('You are on the waiting list'),
    'logoUrl' => $logoUrl,
    'ctaUrl' => $homeUrl,
    'ctaLabel' => __('Visit the house'),
])
    <p style="margin:0;">
        {{ __('Heritage Nº 001 is currently unavailable. We will keep you posted.') }}
    </p>
@endcomponent
