@component('emails.layouts.maison', [
    'title' => $title,
    'logoUrl' => $logoUrl,
    'ctaUrl' => $ctaUrl,
    'ctaLabel' => $ctaLabel,
])
    <p style="margin:0;">
        {{ $intro }}
    </p>
@endcomponent
