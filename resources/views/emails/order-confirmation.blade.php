<x-mail::message>
# {{ __('Bestelling bevestigd') }}

{{ __('Bedankt voor uw aankoop van Heritage No.001 — Founding Edition.') }}

**{{ __('Editienummer') }}:** No.{{ str_pad((string) $order->edition_number, 3, '0', STR_PAD_LEFT) }} / 100

**{{ __('Referentie') }}:** {{ $order->reference() }}

{{ __('Uw Digital Heritage Passport verschijnt in uw account zodra de betaling is bevestigd.') }}

{{ config('app.name') }}
</x-mail::message>
