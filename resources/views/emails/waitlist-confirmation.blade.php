@component('emails.layouts.maison', [
    'title' => __('U staat op de wachtlijst'),
    'logoUrl' => $logoUrl,
    'ctaUrl' => $homeUrl,
    'ctaLabel' => __('Bezoek het huis'),
])
    <p style="margin:0;">
        {{ __('Heritage No.001 is momenteel niet beschikbaar. Wij houden u op de hoogte.') }}
    </p>
@endcomponent
