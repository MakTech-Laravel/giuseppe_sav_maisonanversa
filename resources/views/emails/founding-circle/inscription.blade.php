@component('emails.layouts.maison', [
    'title' => __('Nº :number is now yours', ['number' => $number]),
    'logoUrl' => $logoUrl,
    'ctaUrl' => $listingUrl,
    'ctaLabel' => __('Manage your register listing'),
])
    <p style="margin:0 0 16px;">
        {{ __('Dear :name,', ['name' => $memberName]) }}
    </p>

    <p style="margin:0 0 16px;">
        {{ __('The withdrawal period has passed. Nº :number is now officially yours — your place in the Founding Circle register is inscribed.', ['number' => $number]) }}
    </p>

    <p style="margin:0;">
        {{ __('You can now choose how your name appears in the public register.') }}
    </p>
@endcomponent
