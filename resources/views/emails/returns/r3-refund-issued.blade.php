@component('emails.layouts.maison', [
    'title' => __('Your refund'),
    'logoUrl' => $logoUrl,
])
    <p style="margin:0 0 16px;">
        {{ __('Hello :name,', ['name' => $orderReturn->user?->first_name ?: $orderReturn->order?->name ?: __('customer')]) }}
    </p>
    <p style="margin:0 0 16px;">
        {{ __('We have refunded €:amount to your original payment method. Depending on your bank, it takes 5 to 10 business days to appear.', [
            'amount' => number_format((float) $orderReturn->refund_amount, 2, ',', ''),
        ]) }}
    </p>
    <p style="margin:0;">
        {{ __('Thank you for your interest in Maison Anversa. We hope to welcome you again.') }}
    </p>
@endcomponent
