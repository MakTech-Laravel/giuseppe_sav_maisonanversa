@component('emails.layouts.maison', [
    'title' => __('We have received your return'),
    'logoUrl' => $logoUrl,
])
    <p style="margin:0 0 16px;">
        {{ __('Hello :name,', ['name' => $orderReturn->user?->first_name ?: $orderReturn->order?->name ?: __('customer')]) }}
    </p>
    <p style="margin:0;">
        {{ __('Your parcel has arrived. We are checking it and will confirm your refund within a few days.') }}
    </p>
@endcomponent
