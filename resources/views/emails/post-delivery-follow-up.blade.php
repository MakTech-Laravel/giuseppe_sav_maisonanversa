@component('emails.layouts.maison', [
    'title' => __('How do you like Heritage Nº 001?'),
    'logoUrl' => $logoUrl,
    'ctaUrl' => $homeUrl,
    'ctaLabel' => __('Visit the house'),
])
    <p style="margin:0 0 16px;">
        {{ __('It has been about thirty days since your edition was delivered. We would love to hear how the house feels to you.') }}
    </p>

    <p style="margin:0;">
        <strong style="color:#291C18;">{{ __('Edition number') }}:</strong>
        {{ __('Nº :number', ['number' => str_pad((string) $order->edition_number, 3, '0', STR_PAD_LEFT)]) }}
    </p>
@endcomponent
