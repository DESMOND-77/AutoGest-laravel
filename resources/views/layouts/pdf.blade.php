{{-- Gabarit dompdf : CSS 2.1 uniquement, mise en page par tables, aucune variable CSS. --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ isset($title) ? $title : config('app.name') }}</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 11px; color: #0F172A; }
        h1 { font-size: 18px; margin: 0 0 4px; color: #1E1B4B; }
        h2 { font-size: 13px; margin: 18px 0 6px; color: #1E1B4B; }
        .muted { color: #475569; }
        .brand { color: #4F46E5; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px 8px; border-bottom: 1px solid #D8DCE6; text-align: left; vertical-align: top; }
        th { background: #EEF0FF; font-size: 10px; text-transform: uppercase; letter-spacing: .4px; color: #475569; }
        .num { text-align: right; }
        .total { font-size: 13px; font-weight: bold; }
        .box { border: 1px solid #D8DCE6; padding: 10px 12px; }
        .badge { display: inline-block; padding: 2px 8px; border: 1px solid #4F46E5; color: #4F46E5; font-size: 10px; }
        .footer { margin-top: 24px; font-size: 9px; color: #5B6577; border-top: 1px solid #D8DCE6; padding-top: 8px; }
    </style>
</head>
<body>
    <table style="margin-bottom: 14px;">
        <tr>
            <td style="border: 0; padding: 0;">
                <h1>{{ config('app.name', 'Auto-GestBoard') }}</h1>
                @isset($subtitle)
                    <p class="muted" style="margin: 0;">{{ $subtitle }}</p>
                @endisset
            </td>
            <td style="border: 0; padding: 0; text-align: right;">
                @isset($reference)
                    <p style="margin: 0;"><strong>{{ $reference }}</strong></p>
                @endisset
                @isset($issuedAt)
                    <p class="muted" style="margin: 0;">{{ $issuedAt }}</p>
                @endisset
            </td>
        </tr>
    </table>

    {{ $slot }}

    <p class="footer">
        {{ config('app.name', 'Auto-GestBoard') }} — document généré le {{ now()->format('d/m/Y à H:i') }}.
    </p>
</body>
</html>
