@component('emails.layouts.maison', [
    'title' => __('Your return request'),
    'logoUrl' => $logoUrl,
    'ctaUrl' => $returnUrl,
    'ctaLabel' => __('View my return'),
])
    <p style="margin:0 0 16px;">
        {{ __('Hello :name,', ['name' => $orderReturn->user?->first_name ?: $orderReturn->order?->name ?: __('customer')]) }}
    </p>
    <p style="margin:0 0 16px;">
        {{ __('We have received your return request for order :order. Your return number is :rma.', [
            'order' => $orderReturn->order?->reference() ?? '',
            'rma' => $orderReturn->number,
        ]) }}
    </p>
    <p style="margin:0 0 16px;">
        {{ __('Ship the item within 14 days to:') }}
        <br>
        <strong style="color:#291C18;">{{ $returnAddress }}</strong>
    </p>
    <p style="margin:0 0 16px;">
        {{ __('Include everything that came with it: box, Heritage Passport, cards and accessories. Put your return number on the parcel. The cost of return shipping is yours; we recommend an insured shipment with tracking. You can add your tracking number in your account.') }}
    </p>
    <p style="margin:0;">
        {{ __('Once we have received and checked your return, we refund you within 14 days.') }}
    </p>
@endcomponent
