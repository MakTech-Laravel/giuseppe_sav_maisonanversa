@component('emails.layouts.maison', [
    'title' => __('New order'),
    'logoUrl' => $logoUrl,
])
    <p style="margin:0 0 12px;">
        <strong style="color:#291C18;">{{ __('Reference') }}:</strong>
        {{ $order->reference() }}
    </p>

    <p style="margin:0 0 12px;">
        <strong style="color:#291C18;">{{ __('Customer') }}:</strong>
        {{ $order->name }} ({{ $order->email }})
    </p>

    <p style="margin:0 0 12px;">
        <strong style="color:#291C18;">{{ __('Product') }}:</strong>
        {{ $order->product?->translated('name', 'nl') ?? __('Product') }}
    </p>

    @if ($order->edition_number !== null)
        <p style="margin:0 0 12px;">
            <strong style="color:#291C18;">{{ __('Edition number') }}:</strong>
            Nº {{ str_pad((string) $order->edition_number, 3, '0', STR_PAD_LEFT) }}
        </p>
    @endif

    <p style="margin:0 0 16px;">
        <strong style="color:#291C18;">{{ __('Amount') }}:</strong>
        {{ $order->amount }} {{ strtoupper($order->currency) }}
    </p>

    @if (filled($order->shipping_line1))
        <p style="margin:0;color:#8A7D72;font-size:14px;white-space:pre-line;">
            <strong style="color:#291C18;">{{ __('Shipping address') }}:</strong>
            {{ $order->shipping_line1 }}@if (filled($order->shipping_line2)), {{ $order->shipping_line2 }}@endif

            {{ $order->shipping_postal_code }} {{ $order->shipping_city }}

            {{ $order->shipping_country }}
        </p>
    @endif
@endcomponent
