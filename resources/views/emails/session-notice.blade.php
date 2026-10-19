@component('emails.layouts.maison', [
    'title' => $title,
    'logoUrl' => $logoUrl,
    'ctaUrl' => $ctaUrl,
    'ctaLabel' => $ctaLabel,
    'secondaryCtaUrl' => $secondaryCtaUrl ?? null,
    'secondaryCtaLabel' => $secondaryCtaLabel ?? null,
])
    <p style="margin:0 0 24px;">
        {{ $intro }}
    </p>

    @if (filled($timingNote ?? null))
        <p style="margin:0 0 24px;padding:12px 16px;border-left:2px solid #8D705A;background-color:#EBE0D5;color:#291C18;text-align:left;font-weight:bold;">{{ $timingNote }}</p>
    @endif

    @if ($rows !== [])
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px;border-top:1px solid rgba(141,112,90,0.25);text-align:left;">
            @foreach ($rows as $row)
                <tr>
                    <td style="padding:10px 12px 10px 0;border-bottom:1px solid rgba(141,112,90,0.25);font-family:Arial,Helvetica,sans-serif;font-size:11px;letter-spacing:0.14em;text-transform:uppercase;color:#8D705A;white-space:nowrap;vertical-align:top;">
                        {{ $row['label'] }}
                    </td>
                    <td style="padding:10px 0;border-bottom:1px solid rgba(141,112,90,0.25);font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#291C18;vertical-align:top;">
                        {{ $row['value'] }}
                    </td>
                </tr>
            @endforeach
        </table>
    @endif

    @if ($routeUrl)
        <p style="margin:0 0 24px;">
            <a href="{{ $routeUrl }}" style="color:#8D705A;text-decoration:underline;">{{ __('Route openen') }}</a>
        </p>
    @endif

    @if ($players !== [])
        <p style="margin:0 0 8px;font-size:11px;letter-spacing:0.14em;text-transform:uppercase;color:#8D705A;">
            {{ __('Spelers') }}
        </p>
        <p style="margin:0 0 24px;color:#291C18;">
            {{ implode(' · ', $players) }}
        </p>
    @endif

    @if (filled($shareUrl ?? null))
        <p style="margin:0 0 8px;font-size:11px;letter-spacing:0.14em;text-transform:uppercase;color:#8D705A;">
            {{ __('Deel deze link') }}
        </p>
        <p style="margin:0 0 24px;padding:12px 16px;border:1px dashed rgba(141,112,90,0.5);background-color:#FBF8F4;word-break:break-all;">
            <a href="{{ $shareUrl }}" style="color:#291C18;text-decoration:underline;">{{ $shareUrl }}</a>
        </p>
    @endif

    @if (filled($organiserMessage))
        <p style="margin:0 0 8px;font-size:11px;letter-spacing:0.14em;text-transform:uppercase;color:#8D705A;">
            {{ __('Bericht van de organisator') }}
        </p>
        <p style="margin:0 0 24px;padding:12px 16px;border-left:2px solid #8D705A;background-color:#EBE0D5;color:#291C18;text-align:left;white-space:pre-line;">{{ $organiserMessage }}</p>
    @endif
@endcomponent
