@component('emails.layouts.maison', [
    'title' => __('Heritage Nº 001 is sold out'),
    'logoUrl' => $logoUrl,
    'ctaUrl' => $homeUrl,
    'ctaLabel' => __('Visit the house'),
])
    <p style="margin:0 0 16px;">
        {{ __('The Founding Edition is fully reserved. Nº 001 stays in the Maison Anversa archive.') }}
    </p>

    <p style="margin:0;color:#8A7D72;font-size:14px;">
        {{ __('Subscribe to the Heritage Letter for the next chapter.') }}
    </p>
@endcomponent
