<x-mail::message>
# {{ __('Nieuwe bestelling') }}

**{{ __('Referentie') }}:** {{ $order->reference() }}

**{{ __('Klant') }}:** {{ $order->name }} ({{ $order->email }})

**{{ __('Product') }}:** {{ $order->product?->translated('name', 'nl') ?? __('Product') }}

@if($order->edition_number !== null)
**{{ __('Editienummer') }}:** No.{{ str_pad((string) $order->edition_number, 3, '0', STR_PAD_LEFT) }}
@endif

**{{ __('Bedrag') }}:** {{ $order->amount }} {{ strtoupper($order->currency) }}

@if(filled($order->shipping_line1))
**{{ __('Verzendadres') }}:**
{{ $order->shipping_line1 }}@if(filled($order->shipping_line2)), {{ $order->shipping_line2 }}@endif
{{ $order->shipping_postal_code }} {{ $order->shipping_city }}
{{ $order->shipping_country }}
@endif

{{ config('app.name') }}
</x-mail::message>
