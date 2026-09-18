@component('emails.layouts.maison', [
    'title' => __('Bestelupdate'),
    'logoUrl' => $logoUrl,
    'ctaUrl' => $homeUrl,
    'ctaLabel' => __('Bezoek het huis'),
])
    <p style="margin:0 0 16px;">
        {{ __('Uw bestelling :reference is bijgewerkt.', ['reference' => $order->reference()]) }}
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
        <p style="margin:0;">
            <strong style="color:#291C18;">{{ __('Editienummer') }}:</strong>
            {{ __('Nr. :number', ['number' => str_pad((string) $order->edition_number, 3, '0', STR_PAD_LEFT)]) }}
            / {{ (int) $order->product->edition_total }}
        </p>
    @endif
@endcomponent
