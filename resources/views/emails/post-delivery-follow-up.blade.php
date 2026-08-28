<x-mail::message>
# {{ __('Hoe bevalt Heritage No.001?') }}

{{ __('Het is ongeveer dertig dagen sinds uw editie is geleverd. Wij horen graag hoe het huis voor u aanvoelt.') }}

{{ __('Editienummer') }}: {{ __('Nr. :number', ['number' => str_pad((string) $order->edition_number, 3, '0', STR_PAD_LEFT)]) }}

{{ config('app.name') }}
</x-mail::message>
