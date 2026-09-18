@component('emails.layouts.maison', [
    'title' => __('Wachtwoord opnieuw instellen'),
    'logoUrl' => $logoUrl,
    'ctaUrl' => $ctaUrl,
    'ctaLabel' => $ctaLabel,
])
    <p style="margin:0 0 16px;">
        {{ __('U ontvangt deze e-mail omdat we een verzoek hebben ontvangen om het wachtwoord van uw account opnieuw in te stellen.') }}
    </p>
    <p style="margin:0 0 16px;">
        {{ __('Deze link verloopt over :count minuten.', ['count' => $expireMinutes]) }}
    </p>
    <p style="margin:0;">
        {{ __('Als u geen wachtwoordreset heeft aangevraagd, hoeft u niets te doen.') }}
    </p>
@endcomponent
