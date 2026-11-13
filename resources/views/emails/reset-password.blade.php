@component('emails.layouts.maison', [
    'title' => __('Reset password'),
    'logoUrl' => $logoUrl,
])
    <p style="margin:0 0 16px;">
        {{ __('Use this one-time code to set a new password. Do not share the code with anyone.') }}
    </p>
    <p style="margin:0 0 24px;font-family:Georgia,'Times New Roman',serif;font-size:32px;letter-spacing:0.35em;color:#291C18;">
        {{ $otp }}
    </p>
    <p style="margin:0 0 16px;">
        {{ __('This code expires in :count minutes.', ['count' => $expireMinutes]) }}
    </p>
    <p style="margin:0;">
        {{ __('If you did not request a password reset, no further action is required.') }}
    </p>
@endcomponent
