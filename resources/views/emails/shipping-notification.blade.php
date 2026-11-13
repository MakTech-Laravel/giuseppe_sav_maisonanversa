@component('emails.layouts.maison', [
    'title' => __('Jouw editie is onderweg'),
    'logoUrl' => $logoUrl,
    'ctaUrl' => $homeUrl,
    'ctaLabel' => __('Visit the house'),
])
    <p style="margin:0 0 16px;">
        {{ __('Heritage Nº 001 — Nº :number', ['number' => str_pad((string) $order->edition_number, 3, '0', STR_PAD_LEFT)]) }}
        {{ __('is verzonden.') }}
    </p>

    <p style="margin:0 0 16px;">
        <strong style="color:#291C18;">{{ __('Reference') }}:</strong>
        {{ $order->reference() }}
    </p>

    <p style="margin:0;">
        {{ __('Van gedachten veranderd? Je kunt binnen 14 dagen retourneren via je account.') }}
        <a href="{{ $returnsUrl }}" style="color:#8A6A3B;">{{ __('Start a return') }}</a>
    </p>
@endcomponent
