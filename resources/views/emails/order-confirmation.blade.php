<x-mail::message>
# {{ __('Bestelling bevestigd') }}

{{ __('Bedankt voor uw aankoop van :product.', ['product' => $order->product?->translated('name', $order->locale) ?? __('Product')]) }}

@if($order->edition_number !== null && (int) ($order->product?->edition_total ?? 0) > 0)
**{{ __('Editienummer') }}:** No.{{ str_pad((string) $order->edition_number, 3, '0', STR_PAD_LEFT) }} / {{ (int) $order->product->edition_total }}
@endif

**{{ __('Referentie') }}:** {{ $order->reference() }}

@if($order->product?->grants_founding_circle)
{{ __('Uw Digital Heritage Passport verschijnt in uw account zodra de betaling is bevestigd.') }}
@endif

{{ config('app.name') }}
</x-mail::message>
