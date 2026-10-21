@component('emails.layouts.maison', [
    'title' => __('Je terugbetaling'),
    'logoUrl' => $logoUrl,
])
    <p style="margin:0 0 16px;">
        {{ __('Dag :name,', ['name' => $orderReturn->user?->first_name ?: $orderReturn->order?->name ?: __('klant')]) }}
    </p>
    <p style="margin:0 0 16px;">
        {{ __('We hebben €:amount teruggestort via je oorspronkelijke betaalmiddel. Afhankelijk van je bank duurt het 5 tot 10 werkdagen voor je het ziet.', [
            'amount' => number_format((float) $orderReturn->refund_amount, 2, ',', ''),
        ]) }}
    </p>
    <p style="margin:0;">
        {{ __('Bedankt voor je interesse in Maison Anversa. We hopen je opnieuw te verwelkomen.') }}
    </p>
@endcomponent
