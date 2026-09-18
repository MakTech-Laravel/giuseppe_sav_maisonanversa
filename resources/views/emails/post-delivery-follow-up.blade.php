@component('emails.layouts.maison', [
    'title' => __('Hoe bevalt Heritage No.001?'),
    'logoUrl' => $logoUrl,
    'ctaUrl' => $homeUrl,
    'ctaLabel' => __('Bezoek het huis'),
])
    <p style="margin:0 0 16px;">
        {{ __('Het is ongeveer dertig dagen sinds uw editie is geleverd. Wij horen graag hoe het huis voor u aanvoelt.') }}
    </p>

    <p style="margin:0;">
        <strong style="color:#291C18;">{{ __('Editienummer') }}:</strong>
        {{ __('Nr. :number', ['number' => str_pad((string) $order->edition_number, 3, '0', STR_PAD_LEFT)]) }}
    </p>
@endcomponent
