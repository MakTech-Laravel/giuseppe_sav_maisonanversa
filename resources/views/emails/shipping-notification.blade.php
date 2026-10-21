@component('emails.layouts.maison', [
    'title' => __('Uw editie is onderweg'),
    'logoUrl' => $logoUrl,
    'ctaUrl' => $homeUrl,
    'ctaLabel' => __('Bezoek het huis'),
])
    <p style="margin:0 0 16px;">
        {{ __('Heritage No.001 — Nr. :number', ['number' => str_pad((string) $order->edition_number, 3, '0', STR_PAD_LEFT)]) }}
        {{ __('is verzonden.') }}
    </p>

    <p style="margin:0 0 16px;">
        <strong style="color:#291C18;">{{ __('Referentie') }}:</strong>
        {{ $order->reference() }}
    </p>

    <p style="margin:0;">
        {{ __('Van gedachten veranderd? Je kunt binnen 14 dagen retourneren via je account.') }}
        <a href="{{ $returnsUrl }}" style="color:#8A6A3B;">{{ __('Start een retour') }}</a>
    </p>
@endcomponent
