<!DOCTYPE html>
<html lang="fr" data-mode="light" data-theme="aurore">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Document' }}</title>
    @vite(['resources/css/print.css'])
</head>
<body class="print-root">
    <div class="print-actions">
        <button type="button" onclick="window.print()" class="print-button">Imprimer</button>
        <a href="{{ $backUrl ?? url()->previous() }}" class="print-link">Retour</a>
    </div>
    {{ $slot }}
</body>
</html>
