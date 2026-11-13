@component('emails.layouts.maison', [
    'title' => __('Verify email address'),
    'logoUrl' => $logoUrl,
    'ctaUrl' => $ctaUrl,
    'ctaLabel' => $ctaLabel,
])
    <p style="margin:0 0 16px;">
        {{ __('Please click the button below to verify your email address.') }}
    </p>
    <p style="margin:0;">
        {{ __('If you did not create an account, no further action is required.') }}
    </p>
@endcomponent
