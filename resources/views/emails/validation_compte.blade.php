<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Validation du compte</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f8;font-family:Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="padding:20px 0;background:#f4f6f8;">
<tr>
<td align="center">

<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;border:1px solid #e5e7eb;overflow:hidden;">

    <tr>
        <td style="background:#0f766e;padding:25px;color:#fff;">
            <h2 style="margin:0;">
                COMPASSION PHARMACIE
            </h2>

            <p style="margin-top:8px;">
                Validation de votre compte utilisateur
            </p>
        </td>
    </tr>

    <tr>
        <td style="padding:30px;line-height:1.7;color:#333;">

            <p>Bonjour <strong>{{ $nom }}</strong>,</p>

            <p>
                Un compte utilisateur a été créé pour vous sur la plateforme
                <strong>Compassion Pharmacie</strong>.
            </p>

            <p>
                Pour activer votre compte et définir votre mot de passe,
                veuillez cliquer sur le bouton ci-dessous :
            </p>

            <div style="text-align:center;margin:35px 0;">

                <a href="{{ $lienValidation }}"
                   style="
                   background:#0f766e;
                   color:#ffffff;
                   text-decoration:none;
                   padding:15px 30px;
                   border-radius:6px;
                   display:inline-block;
                   font-weight:bold;
                   ">
                    Valider mon compte
                </a>

            </div>

            <p>
                Si le bouton ne fonctionne pas, copiez et collez le lien suivant dans votre navigateur :
            </p>

            <p style="word-break:break-all;color:#0f766e;">
                {{ $lienValidation }}
            </p>

            <p>
                Si vous n'êtes pas concerné par cette création de compte,
                vous pouvez ignorer ce message.
            </p>

            <p style="margin-top:25px;">
                Cordialement,<br>
                <strong>L'équipe Compassion Pharmacie</strong>
            </p>

        </td>
    </tr>

    <tr>
        <td style="background:#f4f6f8;padding:15px;text-align:center;font-size:12px;color:#6b7280;">
            © {{ date('Y') }} COMPASSION PHARMACIE<br>
            Votre santé, notre priorité.
        </td>
    </tr>

</table>

</td>
</tr>
</table>

</body>
</html>
