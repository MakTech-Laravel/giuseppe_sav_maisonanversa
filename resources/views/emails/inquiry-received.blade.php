<x-mail::message>
# {{ __('Nieuwe aanvraag') }}

**{{ __('Type') }}:** {{ $inquiry->type->label() }}

**{{ __('Naam') }}:** {{ $inquiry->name }}

**{{ __('E-mail') }}:** {{ $inquiry->email }}

@if (filled($inquiry->phone))
**{{ __('Telefoon') }}:** {{ $inquiry->phone }}
@endif

@if (filled($inquiry->subject))
**{{ __('Onderwerp') }}:** {{ $inquiry->subject }}
@endif

**{{ __('Locale') }}:** {{ $inquiry->locale }}

@if (filled($inquiry->message))
{{ $inquiry->message }}
@endif

@if (! empty($inquiry->meta))
@foreach ($inquiry->meta as $key => $value)
**{{ $key }}:** {{ is_scalar($value) ? $value : json_encode($value) }}

@endforeach
@endif

{{ config('app.name') }}
</x-mail::message>
