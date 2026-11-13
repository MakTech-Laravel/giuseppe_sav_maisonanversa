@component('emails.layouts.maison', [
    'title' => __($inquiry->type->confirmationSubject()),
    'logoUrl' => $logoUrl,
    'ctaUrl' => $homeUrl,
    'ctaLabel' => __('Visit the house'),
])
    <p style="margin:0 0 16px;">
        {{ __('Dear :name,', ['name' => $inquiry->name]) }}
    </p>

    <p style="margin:0 0 16px;">
        {{ __('We have received your message and will confirm personally as soon as possible.') }}
    </p>

    @if (filled($inquiry->subject))
        <p style="margin:0 0 12px;">
            <strong style="color:#291C18;">{{ __('Subject') }}:</strong>
            {{ $inquiry->subject }}
        </p>
    @endif

    @if (filled($inquiry->message))
        <p style="margin:0;color:#8A7D72;font-size:14px;white-space:pre-line;">
            {{ $inquiry->message }}
        </p>
    @endif
@endcomponent
