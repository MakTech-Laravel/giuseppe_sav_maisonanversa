@component('emails.layouts.maison', [
    'title' => __('Start your return'),
    'logoUrl' => $logoUrl,
    'ctaUrl' => $returnUrl,
    'ctaLabel' => __('Open return link'),
])
    <p style="margin:0 0 16px;">
        {{ __('Hello :name,', ['name' => $customerName]) }}
    </p>
    <p style="margin:0 0 16px;">
        {{ __('You requested a return link for order :reference. This link works once and expires soon.', [
            'reference' => $orderReference,
        ]) }}
    </p>
    <p style="margin:0;">
        {{ __('Did you not request this email? Then you can safely ignore it.') }}
    </p>
@endcomponent
