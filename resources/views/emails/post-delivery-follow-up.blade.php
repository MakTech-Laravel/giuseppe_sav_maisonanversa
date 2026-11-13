@component('emails.layouts.maison', [
    'title' => __('Hoe bevalt Heritage Nº 001?'),
    'logoUrl' => $logoUrl,
    'ctaUrl' => $homeUrl,
    'ctaLabel' => __('Visit the house'),
])
    <p style="margin:0 0 16px;">
        {{ __('Het is ongeveer dertig dagen sinds jouw editie is geleverd. Wij horen graag hoe het huis voor je aanvoelt.') }}
    </p>

    <p style="margin:0;">
        <strong style="color:#291C18;">{{ __('Edition number') }}:</strong>
        {{ __('Nº :number', ['number' => str_pad((string) $order->edition_number, 3, '0', STR_PAD_LEFT)]) }}
    </p>
@endcomponent
