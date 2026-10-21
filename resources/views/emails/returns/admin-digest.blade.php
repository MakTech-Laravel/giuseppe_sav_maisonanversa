@component('emails.layouts.maison', [
    'title' => 'Returns needing attention',
    'logoUrl' => $logoUrl,
    'ctaUrl' => $indexUrl,
    'ctaLabel' => 'Open returns',
])
    @foreach ($rows as $row)
        <p style="margin:0 0 12px;">
            <strong>{{ $row['number'] }}</strong>
            · order {{ $row['order'] }}
            · {{ $row['reason'] }}
            · due {{ $row['due'] }}
        </p>
    @endforeach
@endcomponent
