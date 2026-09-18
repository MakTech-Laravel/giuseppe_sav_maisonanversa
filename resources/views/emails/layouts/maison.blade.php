@php
    if (blank($logoUrl ?? null)) {
        throw new InvalidArgumentException('Maison email layout requires logoUrl.');
    }

    $ctaUrl = $ctaUrl ?? null;
    $ctaLabel = $ctaLabel ?? null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background-color:#F3EBE3;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F3EBE3;width:100%;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;width:100%;background-color:#F3EBE3;">
                    <tr>
                        <td align="center" style="padding:0 0 28px;">
                            <img
                                src="{{ $logoUrl }}"
                                width="64"
                                height="64"
                                alt="Maison Anversa"
                                style="display:block;width:64px;height:64px;border:0;outline:none;text-decoration:none;"
                            >
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:0 8px 16px;font-family:Georgia,'Times New Roman',serif;font-size:28px;line-height:1.25;color:#291C18;">
                            {{ $title }}
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:0 8px 24px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="width:48px;height:1px;background-color:#8D705A;font-size:0;line-height:0;">&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:0 8px 28px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.75;color:#41332D;text-align:center;">
                            {{ $slot }}
                        </td>
                    </tr>
                    @if (filled($ctaUrl) && filled($ctaLabel))
                        <tr>
                            <td align="center" style="padding:0 8px 36px;">
                                <a
                                    href="{{ $ctaUrl }}"
                                    style="display:inline-block;padding:14px 28px;border:1px solid #291C18;background-color:#291C18;color:#F3EBE3;font-family:Arial,Helvetica,sans-serif;font-size:11px;letter-spacing:0.18em;text-decoration:none;text-transform:uppercase;"
                                >
                                    {{ $ctaLabel }}
                                </a>
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td align="center" style="padding:24px 8px 0;border-top:1px solid rgba(141,112,90,0.25);font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.6;color:#8A7D72;">
                            Maison Anversa
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
