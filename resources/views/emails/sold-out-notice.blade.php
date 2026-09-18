@component('emails.layouts.maison', [
    'title' => __('Heritage No.001 is uitverkocht'),
    'logoUrl' => $logoUrl,
    'ctaUrl' => $homeUrl,
    'ctaLabel' => __('Bezoek het huis'),
])
    <p style="margin:0 0 16px;">
        {{ __('De Founding Edition is volledig gereserveerd. No.001 blijft in het Maison Anversa-archief.') }}
    </p>

    <p style="margin:0;color:#8A7D72;font-size:14px;">
        {{ __('Schrijf u in op de Heritage Letter voor het volgende hoofdstuk.') }}
    </p>
@endcomponent
