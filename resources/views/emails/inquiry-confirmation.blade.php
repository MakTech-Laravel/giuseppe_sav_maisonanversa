<x-mail::message>
# {{ __($inquiry->type->confirmationSubject()) }}

{{ __('Beste :name,', ['name' => $inquiry->name]) }}

{{ __('Wij hebben uw bericht ontvangen en bevestigen persoonlijk zo snel mogelijk.') }}

@if (filled($inquiry->subject))
**{{ __('Onderwerp') }}:** {{ $inquiry->subject }}
@endif

@if (filled($inquiry->message))
{{ $inquiry->message }}
@endif

{{ config('app.name') }}
</x-mail::message>
