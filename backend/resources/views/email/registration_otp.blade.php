<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body style="
    margin: 0;
    padding: 0;
    background-color: #F8F9F4;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    color: #1E2B22;
">

    <table width="100%" cellpadding="0" cellspacing="0" border="0"
           style="background-color: #F8F9F4; padding: 40px 15px;">
        <tr>
            <td align="center">

                <!-- Main Container -->
                <table width="600" cellpadding="0" cellspacing="0" border="0"
                       style="
                            max-width: 600px;
                            width: 100%;
                            background-color: #FFFFFF;
                            border: 1px solid #DCE3D5;
                            border-radius: 14px;
                            overflow: hidden;
                            box-shadow: 0 8px 24px rgba(30, 43, 34, 0.06);
                       ">

                    <!-- Header -->
                    <tr>
                        <td align="center"
                            style="
                                background-color: #16261F;
                                padding: 34px 30px;
                            ">

                            <img src="https://res.cloudinary.com/drercy9vt/image/upload/v1788588116/Gemini_Generated_Image_m6go6vm6go6vm6go-removebg-preview_guzd4n.png"
                                 alt="Kumpuchea Otel"
                                 width="44"
                                 height="44"
                                 style="
                                    display: block;
                                    margin: 0 auto 12px;
                                    border-radius: 10px;
                                    border: 1px solid rgba(157, 190, 124, 0.5);
                                 ">

                            <div style="
                                font-family: Georgia, 'Times New Roman', serif;
                                font-weight: 600;
                                font-size: 26px;
                                color: #F2F5EC;
                                letter-spacing: 0.5px;
                            ">
                                Kumpuchea Otel
                            </div>

                            <div style="
                                margin-top: 8px;
                                font-size: 11px;
                                color: #7FA35C;
                                letter-spacing: 3px;
                                text-transform: uppercase;
                            ">
                                Management System
                            </div>

                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 38px 45px 28px;">

                            <h2 style="
                                margin: 0 0 20px;
                                color: #16261F;
                                font-family: Georgia, 'Times New Roman', serif;
                                font-weight: 600;
                                font-size: 24px;
                                text-align: center;
                            ">
                                Verify Your Email
                            </h2>

                            <p style="
                                margin: 0 0 16px;
                                font-size: 15px;
                                line-height: 1.7;
                                color: #5E6B5A;
                            ">
                                Hello <strong style="color: #1E2B22;">
                                    {{ $fullName }}
                                </strong>,
                            </p>

                            <p style="
                                margin: 0 0 22px;
                                font-size: 14px;
                                line-height: 1.7;
                                color: #5E6B5A;
                            ">
                                Thank you for using&nbsp;
                                <strong style="color: #16261F;">Kumpuchea Otel</strong>.
                                Please use the verification code below to continue.
                            </p>

                            <!-- OTP Box -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center"
                                        style="
                                            padding: 24px 20px;
                                            background-color: #F1F3ED;
                                            border: 1px solid #DCE3D5;
                                            border-radius: 12px;
                                        ">

                                        <div style="
                                            font-size: 11px;
                                            color: #7A8677;
                                            text-transform: uppercase;
                                            letter-spacing: 2px;
                                            margin-bottom: 12px;
                                        ">
                                            Verification Code
                                        </div>

                                        <div style="
                                            font-family: Georgia, 'Times New Roman', serif;
                                            font-size: 36px;
                                            font-weight: 600;
                                            letter-spacing: 10px;
                                            color: #16261F;
                                            margin-left: 10px;
                                        ">
                                            {{ $otp }}
                                        </div>

                                    </td>
                                </tr>
                            </table>

                            <!-- Expiration -->
                            <p style="
                                margin: 22px 0 8px;
                                text-align: center;
                                font-size: 13px;
                                color: #7A8677;
                            ">
                                This verification code will expire in
                                <strong style="color: #4F7A3B;">
                                    10 minutes
                                </strong>.
                            </p>

                            <!-- Security Notice -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                   style="margin-top: 26px;">
                                <tr>
                                    <td style="
                                        background-color: #EEF1E9;
                                        border-left: 4px solid #7FA35C;
                                        border-radius: 8px;
                                        padding: 14px 16px;
                                    ">

                                        <p style="
                                            margin: 0;
                                            font-size: 13px;
                                            line-height: 1.6;
                                            color: #4F7A3B;
                                        ">
                                            <strong style="color: #16261F;">Security notice:</strong>
                                            Never share this verification code
                                            with anyone. Our hotel team will
                                            never ask you for your OTP.
                                        </p>

                                    </td>
                                </tr>
                            </table>

                            <p style="
                                margin: 28px 0 0;
                                font-size: 14px;
                                line-height: 1.7;
                                color: #5E6B5A;
                            ">
                                Regards,<br>
                                <strong style="color: #16261F;">
                                    Kumpuchea Otel Team
                                </strong>
                            </p>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center"
                            style="
                                background-color: #F1F3ED;
                                padding: 22px 30px;
                                border-top: 1px solid #DCE3D5;
                            ">

                            <p style="
                                margin: 0 0 6px;
                                font-size: 12px;
                                color: #7A8677;
                            ">
                                This is an automated email. Please do not reply.
                            </p>

                            <p style="
                                margin: 0;
                                font-size: 12px;
                                color: #A8B39F;
                            ">
                                &copy; {{ date('Y') }} Kumpuchea Otel.
                                All rights reserved.
                            </p>

                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>