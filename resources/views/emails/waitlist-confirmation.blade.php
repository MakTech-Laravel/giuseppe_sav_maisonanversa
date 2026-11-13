@component('emails.layouts.maison', [
    'title' => __('Je staat op de wachtlijst'),
    'logoUrl' => $logoUrl,
    'ctaUrl' => $homeUrl,
    'ctaLabel' => __('Visit the house'),
])
    <p style="margin:0;">
        {{ __('Heritage Nº 001 is momenteel niet beschikbaar. Wij houden je op de hoogte.') }}
    </p>
@endcomponent
