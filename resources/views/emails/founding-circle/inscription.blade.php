@component('emails.layouts.maison', [
    'title' => __('Nº :number is nu van u', ['number' => $number]),
    'logoUrl' => $logoUrl,
    'ctaUrl' => $listingUrl,
    'ctaLabel' => __('Beheer uw registervermelding'),
])
    <p style="margin:0 0 16px;">
        {{ __('Beste :name,', ['name' => $memberName]) }}
    </p>

    <p style="margin:0 0 16px;">
        {{ __('De bedenktijd is verstreken. Nº :number is nu officieel van u — uw plaats in het Founding Circle-register is ingeschreven.', ['number' => $number]) }}
    </p>

    <p style="margin:0;">
        {{ __('U kunt nu kiezen hoe uw naam in het openbare register verschijnt.') }}
    </p>
@endcomponent
