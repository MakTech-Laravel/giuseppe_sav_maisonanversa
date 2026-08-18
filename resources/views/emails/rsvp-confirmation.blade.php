<x-mail::message>
# {{ __('Uw RSVP is bevestigd') }}

{{ __('U bent aangemeld voor :title.', ['title' => $event->translated('title')]) }}

@if ($event->starts_at)
{{ __('Datum') }}: {{ $event->starts_at->translatedFormat('j F Y H:i') }}
@endif

@if (filled($event->translated('location')))
{{ __('Locatie') }}: {{ $event->translated('location') }}
@endif

{{ config('app.name') }}
</x-mail::message>
