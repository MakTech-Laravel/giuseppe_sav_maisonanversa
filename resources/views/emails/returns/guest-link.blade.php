@component('emails.layouts.maison', [
    'title' => __('Start je retour'),
    'logoUrl' => $logoUrl,
    'ctaUrl' => $returnUrl,
    'ctaLabel' => __('Open retourlink'),
])
    <p style="margin:0 0 16px;">
        {{ __('Dag :name,', ['name' => $customerName]) }}
    </p>
    <p style="margin:0 0 16px;">
        {{ __('Je vroeg een retourlink voor bestelling :reference. Deze link is eenmalig en tijdelijk geldig.', [
            'reference' => $orderReference,
        ]) }}
    </p>
    <p style="margin:0;">
        {{ __('Heb je deze mail niet aangevraagd? Dan kun je hem veilig negeren.') }}
    </p>
@endcomponent
