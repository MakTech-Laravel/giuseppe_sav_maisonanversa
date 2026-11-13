@component('emails.layouts.maison', [
    'title' => __('Heritage Nº 001 is uitverkocht'),
    'logoUrl' => $logoUrl,
    'ctaUrl' => $homeUrl,
    'ctaLabel' => __('Visit the house'),
])
    <p style="margin:0 0 16px;">
        {{ __('De Founding Edition is volledig gereserveerd. Nº 001 blijft in het Maison Anversa-archief.') }}
    </p>

    <p style="margin:0;color:#8A7D72;font-size:14px;">
        {{ __('Schrijf je in op de Heritage Letter voor het volgende hoofdstuk.') }}
    </p>
@endcomponent
