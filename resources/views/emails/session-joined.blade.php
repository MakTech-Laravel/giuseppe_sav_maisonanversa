@component('emails.layouts.maison', [
    'title' => __('Sessie-aanmelding'),
    'logoUrl' => $logoUrl,
    'ctaUrl' => $homeUrl,
    'ctaLabel' => __('Bezoek het huis'),
])
    <p style="margin:0 0 16px;">
        {{ __(':name heeft zich aangemeld voor uw sessie op :when.', [
            'name' => $memberName,
            'when' => $when,
        ]) }}
    </p>
    <p style="margin:0;">
        {{ $location }}
    </p>
@endcomponent
