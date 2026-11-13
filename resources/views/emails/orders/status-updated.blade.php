@component('emails.layouts.maison', [
    'title' => __('Order update'),
    'logoUrl' => $logoUrl,
    'ctaUrl' => $homeUrl,
    'ctaLabel' => __('Visit the house'),
])
    <p style="margin:0 0 16px;">
        {{ __('Your order :reference has been updated.', ['reference' => $order->reference()]) }}
    </p>

    <p style="margin:0 0 12px;">
        <strong style="color:#291C18;">{{ __('Status') }}:</strong>
        {{ $statusLabel }}
    </p>

    @if (filled($event->message))
        <p style="margin:0 0 16px;color:#8A7D72;font-size:14px;">
            {{ $event->message }}
        </p>
    @endif

    @if ($order->edition_number !== null && (int) ($order->product?->edition_total ?? 0) > 0)
        <p style="margin:0 0 16px;">
            <strong style="color:#291C18;">{{ __('Edition number') }}:</strong>
            {{ __('Nº :number', ['number' => str_pad((string) $order->edition_number, 3, '0', STR_PAD_LEFT)]) }}
            / {{ (int) $order->product->edition_total }}
        </p>
    @endif

    @if (! empty($returnsUrl))
        <p style="margin:0;">
            {{ __('Van gedachten veranderd? Je kunt binnen 14 dagen retourneren via je account.') }}
            <a href="{{ $returnsUrl }}" style="color:#8A6A3B;">{{ __('Start a return') }}</a>
        </p>
    @endif
@endcomponent
