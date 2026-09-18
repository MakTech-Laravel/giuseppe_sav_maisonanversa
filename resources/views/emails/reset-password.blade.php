@component('emails.layouts.maison', [
    'title' => __('Wachtwoord opnieuw instellen'),
    'logoUrl' => $logoUrl,
])
    <p style="margin:0 0 16px;">
        {{ __('Gebruik deze eenmalige code om een nieuw wachtwoord in te stellen. Deel de code met niemand.') }}
    </p>
    <p style="margin:0 0 24px;font-family:Georgia,'Times New Roman',serif;font-size:32px;letter-spacing:0.35em;color:#291C18;">
        {{ $otp }}
    </p>
    <p style="margin:0 0 16px;">
        {{ __('Deze code verloopt over :count minuten.', ['count' => $expireMinutes]) }}
    </p>
    <p style="margin:0;">
        {{ __('Als u geen wachtwoordreset heeft aangevraagd, hoeft u niets te doen.') }}
    </p>
@endcomponent
