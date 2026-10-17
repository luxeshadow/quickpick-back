<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réinitialisation du mot de passe</title>
</head>
<body style="font-family:Arial,sans-serif;background:#f4f6f8;padding:40px 15px;">
    <div style="max-width:520px;margin:auto;background:#fff;padding:30px;border-radius:18px;text-align:center;">
        <img src="https://i.postimg.cc/d0Yp0n5v/logo-1.png" alt="Quick Pick" style="max-width:150px;">
        <h2>Réinitialisation du mot de passe</h2>
        <p>Bonjour {{ $user->full_name }},</p>
        <p>Cliquez sur le bouton pour choisir un nouveau mot de passe.</p>
        <p style="margin:30px 0;">
            <a href="{{ $resetUrl }}" style="background:#111827;color:#fff;padding:14px 22px;border-radius:8px;text-decoration:none;">
                Choisir un nouveau mot de passe
            </a>
        </p>
        <p>Ce lien expire dans 30 minutes.</p>
        <p>Si vous n’avez pas demandé cette opération, ignorez cet email.</p>
    </div>
</body>
</html>
