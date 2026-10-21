@component('emails.layouts.maison', [
    'title' => __('We hebben je retour ontvangen'),
    'logoUrl' => $logoUrl,
])
    <p style="margin:0 0 16px;">
        {{ __('Dag :name,', ['name' => $orderReturn->user?->first_name ?: $orderReturn->order?->name ?: __('klant')]) }}
    </p>
    <p style="margin:0;">
        {{ __('Je pakket is aangekomen. We controleren het en bevestigen je terugbetaling binnen enkele dagen.') }}
    </p>
@endcomponent
