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

    @if ($order->product?->slug === \App\Models\Product::FOUNDING_SLUG)
        <p style="margin:0 0 12px;color:#8A7D72;font-size:14px;">
            {{ __('Uw naam staat in het privé-archief van het huis. Hoe u in het publieke register verschijnt, kiest u in uw account.') }}
        </p>
        <p style="margin:0;">
            <a href="{{ $listingUrl }}" style="color:#8A6A3B;">{{ __('Beheer mijn vermelding') }}</a>
        </p>
    @endif
@endcomponent
