<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email</title>
</head>

<body style="
    margin: 0;
    padding: 0;
    background-color: #f4f6f8;
    font-family: Arial, Helvetica, sans-serif;
    color: #333333;
">

    <table width="100%" cellpadding="0" cellspacing="0" border="0"
           style="background-color: #f4f6f8; padding: 40px 15px;">
        <tr>
            <td align="center">

                <!-- Main Container -->
                <table width="600" cellpadding="0" cellspacing="0" border="0"
                       style="
                            max-width: 600px;
                            width: 100%;
                            background-color: #ffffff;
                            border-radius: 12px;
                            overflow: hidden;
                            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
                       ">

                    <!-- Header -->
                    <tr>
                        <td align="center"
                            style="
                                background-color: #0f3d3e;
                                padding: 35px 30px;
                            ">

                            <div style="
                                font-size: 28px;
                                font-weight: bold;
                                color: #ffffff;
                                letter-spacing: 1px;
                            ">
                                HOTEL
                            </div>

                            <div style="
                                margin-top: 8px;
                                font-size: 13px;
                                color: #d4af37;
                                letter-spacing: 3px;
                                text-transform: uppercase;
                            ">
                                Management System
                            </div>

                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 45px 30px;">

                            <h2 style="
                                margin: 0 0 20px;
                                color: #0f3d3e;
                                font-size: 26px;
                                text-align: center;
                            ">
                                Verify Your Email
                            </h2>

                            <p style="
                                margin: 0 0 18px;
                                font-size: 16px;
                                line-height: 1.7;
                                color: #555555;
                            ">
                                Hello <strong style="color: #0f3d3e;">
                                    {{ $fullName }}
                                </strong>,
                            </p>

                            <p style="
                                margin: 0 0 18px;
                                font-size: 15px;
                                line-height: 1.7;
                                color: #666666;
                            ">
                                Thank you for registering with our hotel.
                                Please use the verification code below to
                                confirm your email address.
                            </p>

                            <!-- OTP Box -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center"
                                        style="
                                            padding: 25px 20px;
                                            background-color: #f8f6ef;
                                            border: 1px solid #eadfbf;
                                            border-radius: 10px;
                                        ">

                                        <div style="
                                            font-size: 12px;
                                            color: #888888;
                                            text-transform: uppercase;
                                            letter-spacing: 2px;
                                            margin-bottom: 12px;
                                        ">
                                            Verification Code
                                        </div>

                                        <div style="
                                            font-size: 38px;
                                            font-weight: bold;
                                            letter-spacing: 10px;
                                            color: #0f3d3e;
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
                                font-size: 14px;
                                color: #777777;
                            ">
                                This verification code will expire in
                                <strong style="color: #0f3d3e;">
                                    10 minutes
                                </strong>.
                            </p>

                            <!-- Security Notice -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                   style="margin-top: 25px;">
                                <tr>
                                    <td style="
                                        background-color: #fff8e6;
                                        border-left: 4px solid #d4af37;
                                        padding: 14px 16px;
                                    ">

                                        <p style="
                                            margin: 0;
                                            font-size: 13px;
                                            line-height: 1.6;
                                            color: #6b5a2a;
                                        ">
                                            <strong>Security notice:</strong>
                                            Never share this verification code
                                            with anyone. Our hotel team will
                                            never ask you for your OTP.
                                        </p>

                                    </td>
                                </tr>
                            </table>

                            <p style="
                                margin: 30px 0 0;
                                font-size: 15px;
                                line-height: 1.7;
                                color: #555555;
                            ">
                                Regards,<br>
                                <strong style="color: #0f3d3e;">
                                    Hotel Management Team
                                </strong>
                            </p>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center"
                            style="
                                background-color: #f8f9fa;
                                padding: 25px 30px;
                                border-top: 1px solid #eeeeee;
                            ">

                            <p style="
                                margin: 0 0 8px;
                                font-size: 12px;
                                color: #999999;
                            ">
                                This is an automated email. Please do not reply.
                            </p>

                            <p style="
                                margin: 0;
                                font-size: 12px;
                                color: #aaaaaa;
                            ">
                                &copy; {{ date('Y') }} Hotel Management System.
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
