@component('emails.layouts.maison', [
    'title' => __('Jouw RSVP is bevestigd'),
    'logoUrl' => $logoUrl,
    'ctaUrl' => $homeUrl,
    'ctaLabel' => __('Visit the house'),
])
    <p style="margin:0 0 16px;">
        {{ __('Je bent aangemeld voor :title.', ['title' => $event->translated('title')]) }}
    </p>

    @if ($event->starts_at)
        <p style="margin:0 0 12px;">
            <strong style="color:#291C18;">{{ __('Date') }}:</strong>
            {{ $event->starts_at->translatedFormat('j F Y H:i') }}
        </p>
    @endif

    @if (filled($event->translated('location')))
        <p style="margin:0;">
            <strong style="color:#291C18;">{{ __('Location') }}:</strong>
            {{ $event->translated('location') }}
        </p>
    @endif
@endcomponent
