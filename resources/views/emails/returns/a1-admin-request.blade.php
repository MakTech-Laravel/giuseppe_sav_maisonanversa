@component('emails.layouts.maison', [
    'title' => 'New return request',
    'logoUrl' => $logoUrl,
    'ctaUrl' => $adminUrl,
    'ctaLabel' => 'Open in admin',
])
    <p style="margin:0 0 12px;">
        New return request <strong>{{ $orderReturn->number }}</strong>
    </p>
    <p style="margin:0 0 8px;">Order: {{ $orderReturn->order?->reference() }}</p>
    <p style="margin:0 0 8px;">Customer: {{ $orderReturn->user?->name ?? $orderReturn->order?->name }}</p>
    <p style="margin:0 0 8px;">
        Items:
        {{ $orderReturn->items->map(fn ($item) => $item->product?->name)->filter()->implode(', ') ?: ($orderReturn->order?->product?->name ?? '—') }}
    </p>
    <p style="margin:0 0 8px;">
        Reason: {{ $orderReturn->reason?->label() ?? 'none' }}
    </p>
    <p style="margin:0;">
        Refund due by: {{ $orderReturn->refund_due_by?->toDateString() }}
    </p>
@endcomponent
