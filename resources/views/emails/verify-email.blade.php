@component('emails.layouts.maison', [
    'title' => __('E-mailadres verifiëren'),
    'logoUrl' => $logoUrl,
    'ctaUrl' => $ctaUrl,
    'ctaLabel' => $ctaLabel,
])
    <p style="margin:0 0 16px;">
        {{ __('Klik op de knop hieronder om uw e-mailadres te bevestigen.') }}
    </p>
    <p style="margin:0;">
        {{ __('Als u geen account heeft aangemaakt, hoeft u niets te doen.') }}
    </p>
@endcomponent
