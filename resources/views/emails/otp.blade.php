@php
    $app = config('app.name', 'Auto-GestBoard');
    $minutes = (int) config('services.email_otp.expiry_minutes', 10);
@endphp
<!DOCTYPE html>
<html lang="fr" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>Votre code de vérification</title>
    <style>
        /* Les clients mail ignorent souvent <style> : tout le rendu critique est aussi en attribut. */
        body { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        table { border-collapse: collapse; }
        .code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; letter-spacing: .28em; }
        @media (prefers-color-scheme: dark) {
            .bg { background: #0B1120 !important; }
            .card { background: #111827 !important; border-color: #1F2937 !important; }
            .h1, .code { color: #F8FAFC !important; }
            .text { color: #CBD5E1 !important; }
            .muted { color: #94A3B8 !important; }
            .codebox { background: #1E293B !important; border-color: #334155 !important; }
        }
        @media screen and (max-width: 480px) {
            .card { padding: 24px !important; }
            .code { font-size: 26px !important; }
        }
    </style>
</head>
<body class="bg" style="margin:0; padding:0; background:#F1F5F9; -webkit-text-size-adjust:100%;">
    <div style="display:none; font-size:1px; color:#F1F5F9; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">
        Votre code de vérification {{ $app }} : {{ $code }}. Il expire dans {{ $minutes }} minutes.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F1F5F9; padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;">

                    <tr>
                        <td style="padding:0 8px 20px; font-family:'Inter',system-ui,-apple-system,'Segoe UI',Roboto,Arial,sans-serif;">
                            <span style="display:inline-block; width:32px; height:32px; border-radius:9px; background:#4F46E5; color:#FFFFFF; font-weight:700; font-size:15px; line-height:32px; text-align:center; vertical-align:middle;">A</span>
                            <span class="h1" style="color:#0F172A; font-size:15px; font-weight:600; vertical-align:middle; padding-left:10px;">{{ $app }}</span>
                        </td>
                    </tr>

                    <tr>
                        <td class="card" style="background:#FFFFFF; border:1px solid #E2E8F0; border-radius:16px; padding:32px; font-family:'Inter',system-ui,-apple-system,'Segoe UI',Roboto,Arial,sans-serif;">
                            <h1 class="h1" style="margin:0 0 8px; font-size:21px; line-height:1.3; color:#0F172A; font-weight:700;">Votre code de vérification</h1>
                            <p class="text" style="margin:0 0 20px; font-size:15px; line-height:1.6; color:#475569;">
                                Bonjour, voici le code demandé pour confirmer votre adresse e-mail. Saisissez-le dans la fenêtre ouverte dans votre navigateur.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td class="codebox" align="center" style="background:#EEF2FF; border:1px solid #C7D2FE; border-radius:12px; padding:20px 12px;">
                                        <span class="code" style="font-size:32px; font-weight:700; color:#3730A3;">{{ $code }}</span>
                                    </td>
                                </tr>
                            </table>

                            <p class="text" style="margin:20px 0 0; font-size:15px; line-height:1.6; color:#475569;">
                                Ce code expire dans <strong style="color:#0F172A;">{{ $minutes }} minutes</strong> et ne peut être utilisé qu’une seule fois.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:24px;">
                                <tr>
                                    <td style="border-top:1px solid #E2E8F0; padding-top:16px;">
                                        <p class="muted" style="margin:0; font-size:13px; line-height:1.6; color:#64748B;">
                                            Vous n’êtes pas à l’origine de cette demande ? Ignorez cet e-mail : sans saisie du code, aucune vérification n’aura lieu.
                                            Si les demandes se répètent, contactez votre établissement.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td class="muted" style="padding:20px 8px 0; text-align:center; font-family:'Inter',system-ui,-apple-system,'Segoe UI',Roboto,Arial,sans-serif; font-size:12px; line-height:1.6; color:#64748B;">
                            {{ $app }} — gestion d’auto-école.<br>
                            Cet e-mail a été envoyé automatiquement, merci de ne pas y répondre.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
