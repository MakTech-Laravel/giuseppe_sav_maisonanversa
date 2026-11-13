@component('emails.layouts.maison', [
    'title' => __('Order confirmed'),
    'logoUrl' => $logoUrl,
    'ctaUrl' => $homeUrl,
    'ctaLabel' => __('Visit the house'),
])
    <p style="margin:0 0 16px;">
        {{ __('Thank you for your purchase of :product.', ['product' => $order->product?->translated('name', $order->locale) ?? __('Product')]) }}
    </p>

    @if ($order->edition_number !== null && (int) ($order->product?->edition_total ?? 0) > 0)
        <p style="margin:0 0 12px;">
            <strong style="color:#291C18;">{{ __('Edition number') }}:</strong>
            {{ __('Nº :number', ['number' => str_pad((string) $order->edition_number, 3, '0', STR_PAD_LEFT)]) }}
            / {{ (int) $order->product->edition_total }}
        </p>
    @endif

    <p style="margin:0 0 16px;">
        <strong style="color:#291C18;">{{ __('Reference') }}:</strong>
        {{ $order->reference() }}
    </p>

    @if ($order->product?->slug === \App\Models\Product::FOUNDING_SLUG)
        <p style="margin:0 0 12px;color:#8A7D72;font-size:14px;">
            {{ __('Your name is in the private archive of the house. You choose in your account how you appear in the public register.') }}
        </p>
        <p style="margin:0 0 16px;">
            <a href="{{ $listingUrl }}" style="color:#8A6A3B;">{{ __('Manage my listing') }}</a>
        </p>
    @endif

    <p style="margin:0;color:#8A7D72;font-size:14px;">
        {{ __('Van gedachten veranderd? Je kunt binnen 14 dagen retourneren via je account.') }}
        <a href="{{ $returnsUrl }}" style="color:#8A6A3B;">{{ __('Start a return') }}</a>
    </p>
@endcomponent
