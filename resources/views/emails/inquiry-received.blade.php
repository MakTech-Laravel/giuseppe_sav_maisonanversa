@component('emails.layouts.maison', [
    'title' => __('Nieuwe aanvraag'),
    'logoUrl' => $logoUrl,
])
    <p style="margin:0 0 12px;">
        <strong style="color:#291C18;">{{ __('Type') }}:</strong>
        {{ $inquiry->type->label() }}
    </p>

    <p style="margin:0 0 12px;">
        <strong style="color:#291C18;">{{ __('Naam') }}:</strong>
        {{ $inquiry->name }}
    </p>

    <p style="margin:0 0 12px;">
        <strong style="color:#291C18;">{{ __('E-mail') }}:</strong>
        {{ $inquiry->email }}
    </p>

    @if (filled($inquiry->phone))
        <p style="margin:0 0 12px;">
            <strong style="color:#291C18;">{{ __('Telefoon') }}:</strong>
            {{ $inquiry->phone }}
        </p>
    @endif

    @if (filled($inquiry->subject))
        <p style="margin:0 0 12px;">
            <strong style="color:#291C18;">{{ __('Onderwerp') }}:</strong>
            {{ $inquiry->subject }}
        </p>
    @endif

    <p style="margin:0 0 16px;">
        <strong style="color:#291C18;">{{ __('Locale') }}:</strong>
        {{ $inquiry->locale }}
    </p>

    @if (filled($inquiry->message))
        <p style="margin:0 0 16px;color:#8A7D72;font-size:14px;white-space:pre-line;">
            {{ $inquiry->message }}
        </p>
    @endif

    @if (! empty($inquiry->meta))
        @foreach ($inquiry->meta as $key => $value)
            <p style="margin:0 0 8px;">
                <strong style="color:#291C18;">{{ $key }}:</strong>
                {{ is_scalar($value) ? $value : json_encode($value) }}
            </p>
        @endforeach
    @endif
@endcomponent
