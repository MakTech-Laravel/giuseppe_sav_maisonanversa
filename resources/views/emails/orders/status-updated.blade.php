<x-mail::message>
# {{ __('Bestelupdate') }}

{{ __('Uw bestelling :reference is bijgewerkt.', ['reference' => $order->reference()]) }}

**{{ __('Status') }}:** {{ $statusLabel }}

@if(filled($event->message))
{{ $event->message }}
@endif

@if($order->edition_number !== null && (int) ($order->product?->edition_total ?? 0) > 0)
**{{ __('Editienummer') }}:** No.{{ str_pad((string) $order->edition_number, 3, '0', STR_PAD_LEFT) }} / {{ (int) $order->product->edition_total }}
@endif

{{ config('app.name') }}
</x-mail::message>
