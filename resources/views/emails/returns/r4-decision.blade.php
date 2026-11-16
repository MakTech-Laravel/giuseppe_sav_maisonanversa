@component('emails.layouts.maison', [
    'title' => __('About your return'),
    'logoUrl' => $logoUrl,
])
    <p style="margin:0 0 16px;">
        {{ __('Hello :name,', ['name' => $orderReturn->user?->first_name ?: $orderReturn->order?->name ?: __('customer')]) }}
    </p>
    <p style="margin:0 0 16px;">
        {{ __('We have checked your return :rma. :reason', [
            'rma' => $orderReturn->number,
            'reason' => $orderReturn->refund_reason ?? '',
        ]) }}
    </p>
    @if ($isPartial)
        <p style="margin:0 0 16px;">
            {{ __('That is why we refunded €:amount of €:full.', [
                'amount' => number_format((float) $orderReturn->refund_amount, 2, ',', ''),
                'full' => number_format((float) $orderReturn->expected_refund_amount, 2, ',', ''),
            ]) }}
        </p>
    @else
        <p style="margin:0 0 16px;">
            {{ __('Unfortunately we cannot accept this return.') }}
        </p>
    @endif
    <p style="margin:0;">
        {{ __('Any questions? Just reply to this email.') }}
    </p>
@endcomponent
