@component('emails.layouts.maison', [
    'title' => __('Over je retour'),
    'logoUrl' => $logoUrl,
])
    <p style="margin:0 0 16px;">
        {{ __('Dag :name,', ['name' => $orderReturn->user?->first_name ?: $orderReturn->order?->name ?: __('klant')]) }}
    </p>
    <p style="margin:0 0 16px;">
        {{ __('We hebben je retour :rma gecontroleerd. :reason', [
            'rma' => $orderReturn->number,
            'reason' => $orderReturn->refund_reason ?? '',
        ]) }}
    </p>
    @if ($isPartial)
        <p style="margin:0 0 16px;">
            {{ __('Daarom hebben we €:amount van €:full terugbetaald.', [
                'amount' => number_format((float) $orderReturn->refund_amount, 2, ',', ''),
                'full' => number_format((float) $orderReturn->expected_refund_amount, 2, ',', ''),
            ]) }}
        </p>
    @else
        <p style="margin:0 0 16px;">
            {{ __('We kunnen deze retour helaas niet aanvaarden.') }}
        </p>
    @endif
    <p style="margin:0;">
        {{ __('Heb je vragen? Antwoord gewoon op deze mail.') }}
    </p>
@endcomponent
