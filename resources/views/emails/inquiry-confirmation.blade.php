@component('emails.layouts.maison', [
    'title' => __($inquiry->type->confirmationSubject()),
    'logoUrl' => $logoUrl,
    'ctaUrl' => $homeUrl,
    'ctaLabel' => __('Bezoek het huis'),
])
    <p style="margin:0 0 16px;">
        {{ __('Beste :name,', ['name' => $inquiry->name]) }}
    </p>

    <p style="margin:0 0 16px;">
        {{ __('Wij hebben uw bericht ontvangen en bevestigen persoonlijk zo snel mogelijk.') }}
    </p>

    @if (filled($inquiry->subject))
        <p style="margin:0 0 12px;">
            <strong style="color:#291C18;">{{ __('Onderwerp') }}:</strong>
            {{ $inquiry->subject }}
        </p>
    @endif

    @if (filled($inquiry->message))
        <p style="margin:0;color:#8A7D72;font-size:14px;white-space:pre-line;">
            {{ $inquiry->message }}
        </p>
    @endif
@endcomponent
