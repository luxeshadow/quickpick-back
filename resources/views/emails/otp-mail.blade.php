<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Quick Pick - Vérification</title>
</head>

<body style="margin:0; padding:0; background:#f4f6f8; font-family:Arial, sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 15px;">
    <tr>
        <td align="center">

            <table width="100%" cellpadding="0" cellspacing="0"
                   style="
                       max-width:520px;
                       background:#ffffff;
                       border-radius:18px;
                       overflow:hidden;
                       box-shadow:0 4px 20px rgba(0,0,0,0.08);
                   ">

                <!-- Header -->
                <tr>
                    <td align="center" style="padding:30px 30px 15px;">
                        <img
                            src="https://znjxmjoupgntcuynhybn.supabase.co/storage/v1/object/public/assets/email/logo_1.png"
                            alt="Quick Pick"
                            width="120"
                            style="
                                display:block;
                                max-width:120px;
                                height:auto;
                                border-radius:12px;
                            "
                        >
                    </td>
                </tr>

                <!-- Content -->
                <tr>
                    <td style="padding:15px 40px 40px; text-align:center;">

                        <h2 style="
                            margin:0 0 15px;
                            color:#111827;
                            font-size:24px;
                        ">
                            Bonjour {{ $user->full_name }}
                        </h2>

                        <p style="
                            color:#6b7280;
                            font-size:15px;
                            line-height:24px;
                            margin-bottom:25px;
                        ">
                            Voici votre code de vérification Quick Pick :
                        </p>

                        <!-- OTP -->
                        <div style="
                            background:#f3f6ff;
                            border-radius:14px;
                            padding:20px;
                            margin:20px 0;
                        ">
                            <span style="
                                font-size:34px;
                                font-weight:bold;
                                letter-spacing:8px;
                                color:#2563eb;
                            ">
                                {{ $otp }}
                            </span>
                        </div>

                        <p style="
                            color:#6b7280;
                            font-size:14px;
                            margin-top:25px;
                        ">
                            Ce code expire dans <strong>10 minutes</strong>.
                        </p>

                        <p style="
                            color:#9ca3af;
                            font-size:12px;
                            margin-top:30px;
                        ">
                            Si vous n'avez pas demandé ce code,
                            vous pouvez ignorer cet email.
                        </p>

                    </td>
                </tr>

            </table>

            <p style="
                color:#9ca3af;
                font-size:12px;
                margin-top:20px;
            ">
                © {{ date('Y') }} Quick Pick
            </p>

        </td>
    </tr>
</table>

</body>
</html>
