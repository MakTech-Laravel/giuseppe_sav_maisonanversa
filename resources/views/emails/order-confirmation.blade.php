@component('emails.layouts.maison', [
    'title' => __('Bestelling bevestigd'),
    'logoUrl' => $logoUrl,
    'ctaUrl' => $homeUrl,
    'ctaLabel' => __('Bezoek het huis'),
])
    <p style="margin:0 0 16px;">
        {{ __('Bedankt voor uw aankoop van :product.', ['product' => $order->product?->translated('name', $order->locale) ?? __('Product')]) }}
    </p>

    @if ($order->edition_number !== null && (int) ($order->product?->edition_total ?? 0) > 0)
        <p style="margin:0 0 12px;">
            <strong style="color:#291C18;">{{ __('Editienummer') }}:</strong>
            {{ __('Nr. :number', ['number' => str_pad((string) $order->edition_number, 3, '0', STR_PAD_LEFT)]) }}
            / {{ (int) $order->product->edition_total }}
        </p>
    @endif

    <p style="margin:0 0 16px;">
        <strong style="color:#291C18;">{{ __('Referentie') }}:</strong>
        {{ $order->reference() }}
    </p>

    @if ($order->product?->grants_founding_circle)
        <p style="margin:0;color:#8A7D72;font-size:14px;">
            {{ __('Uw Digital Heritage Passport verschijnt in uw account zodra de betaling is bevestigd.') }}
        </p>
    @endif
@endcomponent
