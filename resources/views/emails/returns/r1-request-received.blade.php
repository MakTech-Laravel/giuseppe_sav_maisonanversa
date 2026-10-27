@component('emails.layouts.maison', [
    'title' => __('Uw retouraanvraag'),
    'logoUrl' => $logoUrl,
    'ctaUrl' => $returnUrl,
    'ctaLabel' => __('Bekijk mijn retour'),
])
    <p style="margin:0 0 16px;">
        {{ __('Dag :name,', ['name' => $orderReturn->user?->first_name ?: $orderReturn->order?->name ?: __('klant')]) }}
    </p>
    <p style="margin:0 0 16px;">
        {{ __('We hebben je retouraanvraag voor bestelling :order ontvangen. Je retournummer is :rma.', [
            'order' => $orderReturn->order?->reference() ?? '',
            'rma' => $orderReturn->number,
        ]) }}
    </p>
    <p style="margin:0 0 16px;">
        {{ __('Stuur het artikel binnen 14 dagen naar:') }}
        <br>
        <strong style="color:#291C18;">{{ $returnAddress }}</strong>
    </p>
    <p style="margin:0 0 16px;">
        {{ __('Stuur alles mee wat erbij hoorde: doos, Heritage Passport, kaarten en accessoires. Zet je retournummer op het pakket. De kosten van de retourzending zijn voor jou; we raden een verzekerde zending met tracking aan. Je trackingnummer kun je toevoegen in je account.') }}
    </p>
    <p style="margin:0;">
        {{ __('Zodra we je retour ontvangen en gecontroleerd hebben, betalen we je binnen 14 dagen terug.') }}
    </p>
@endcomponent
