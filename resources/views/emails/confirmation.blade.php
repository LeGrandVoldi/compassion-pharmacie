<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code de vérification</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f6f8; font-family:Arial, sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f8; padding:20px 0;">
    <tr>
        <td align="center">

            <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; overflow:hidden; border:1px solid #e5e7eb;">

                <!-- Header -->
                <tr>
                    <td style="background-color:#0f766e; color:#ffffff; padding:20px 30px;">
                        <h2 style="margin:0; font-size:22px; font-weight:700;">
                            COMPASSION PHARMACIE
                        </h2>
                        <p style="margin:6px 0 0; font-size:14px; opacity:0.95;">
                            Vérification de votre adresse e-mail
                        </p>
                    </td>
                </tr>

                <!-- Content -->
                <tr>
                    <td style="padding:30px; color:#333333; font-size:15px; line-height:1.7;">

                        <p style="margin-top:0;">
                            Bonjour {{ $nom }},
                        </p>

                        <p>
                            Merci pour votre inscription sur la plateforme
                            <strong>Compassion Pharmacie</strong>.
                        </p>

                        <p>
                            Pour finaliser la création de votre compte, veuillez utiliser le code de vérification ci-dessous :
                        </p>

                        <div style="
                            text-align:center;
                            margin:30px 0;
                            padding:20px;
                            background:#f0fdfa;
                            border:2px dashed #0f766e;
                            border-radius:8px;
                        ">
                            <span style="
                                font-size:36px;
                                font-weight:bold;
                                letter-spacing:10px;
                                color:#0f766e;
                            ">
                                {{ $code }}
                            </span>
                        </div>

                        <p>
                            Ce code est valable pendant une durée limitée.
                        </p>

                        <p>
                            Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet e-mail en toute sécurité.
                        </p>

                        <p style="margin-top:25px;">
                            Cordialement,<br>
                            <strong>L'équipe Compassion Pharmacie</strong>
                        </p>

                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="background:#f4f6f8; padding:15px 30px; font-size:12px; color:#6b7280; text-align:center;">
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
