<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: sans-serif; color: #1f2937;">
    <p>Bonjour,</p>
    <p>Voici votre code de vérification :</p>
    <p style="font-size: 28px; font-weight: 700; letter-spacing: 0.3em;">{{ $code }}</p>
    <p>Ce code expire dans {{ config('services.email_otp.expiry_minutes') }} minutes.</p>
    <p>Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet e-mail.</p>
</body>
</html>
