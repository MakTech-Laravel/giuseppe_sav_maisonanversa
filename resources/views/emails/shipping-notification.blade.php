<x-mail::message>
# {{ __('Uw editie is onderweg') }}

{{ __('Heritage No.001 — No.') }}{{ str_pad((string) $order->edition_number, 3, '0', STR_PAD_LEFT) }} {{ __('is verzonden.') }}

{{ __('Referentie') }}: {{ $order->reference() }}

{{ config('app.name') }}
</x-mail::message>
