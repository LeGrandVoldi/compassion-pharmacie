<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau message de contact</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f6f8; font-family:Arial, sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f8; padding:20px 0;">
        <tr>
            <td align="center">

                <!-- Conteneur principal -->
                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:10px; overflow:hidden; box-shadow:0 4px 10px rgba(0,0,0,0.05);">

                    <!-- En-tête -->
                    <tr>
                        <td style="background:linear-gradient(135deg, #1f3b73, #2f5aa8); color:#ffffff; padding:22px 30px;">
                            <h2 style="margin:0; font-size:22px;">📩 Nouveau message de contact</h2>
                            <p style="margin:6px 0 0; font-size:14px; opacity:0.95;">
                                SEA CONSULTING
                            </p>
                        </td>
                    </tr>

                    <!-- Contenu -->
                    <tr>
                        <td style="padding:30px; color:#333333; font-size:15px; line-height:1.7;">

                            <p style="margin-top:0;">
                                Vous avez reçu un nouveau message via le formulaire de contact du site <strong>SEA CONSULTING</strong>.
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0; border-collapse:collapse;">

                                <tr>
                                    <td style="padding:10px 0; border-bottom:1px solid #eeeeee; width:35%;">
                                        <strong>Nom complet :</strong>
                                    </td>
                                    <td style="padding:10px 0; border-bottom:1px solid #eeeeee;">
                                        {{ $nom }}
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:10px 0; border-bottom:1px solid #eeeeee;">
                                        <strong>Email :</strong>
                                    </td>
                                    <td style="padding:10px 0; border-bottom:1px solid #eeeeee;">
                                        <a href="mailto:{{ $email }}" style="color:#1f3b73; text-decoration:none;">
                                            {{ $email }}
                                        </a>
                                    </td>
                                </tr>

                                @if(!empty($telephone))
                                <tr>
                                    <td style="padding:10px 0; border-bottom:1px solid #eeeeee;">
                                        <strong>Téléphone :</strong>
                                    </td>
                                    <td style="padding:10px 0; border-bottom:1px solid #eeeeee;">
                                        {{ $telephone }}
                                    </td>
                                </tr>
                                @endif

                                <tr>
                                    <td style="padding:10px 0; border-bottom:1px solid #eeeeee;">
                                        <strong>Sujet :</strong>
                                    </td>
                                    <td style="padding:10px 0; border-bottom:1px solid #eeeeee;">
                                        {{ $sujet }}
                                    </td>
                                </tr>

                            </table>

                            <p style="margin-top:25px; margin-bottom:10px;"><strong>Message :</strong></p>

                            <div style="background-color:#f7f9fb; border-left:4px solid #1f3b73; padding:15px; border-radius:5px; color:#111827;">
                                {!! nl2br(e($contenu)) !!}
                            </div>

                            <p style="margin-top:25px; margin-bottom:0; font-size:14px; color:#555;">
                                Merci de traiter ce message dans les meilleurs délais.
                            </p>
                            <div style="text-align:center; margin-top:30px;">
                               <img src="https://www.seaconsulting.services/assets/images/og-image.jpeg" alt="SEA CONSULTING" style="max-width:120px; height:auto; display:block; margin:0 auto 10px;">
                            </div>

                        </td>
                    </tr>

                    <!-- Pied de page -->
                    <tr>
                        <td style="background-color:#f4f6f8; padding:15px 30px; font-size:12px; color:#666666; text-align:center;">
                            © {{ date('Y') }} SEA CONSULTING SARLU — Innovation – Excellence – Respect de délai
                            <br>
                            Message envoyé depuis le site officiel SEA CONSULTING.
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
