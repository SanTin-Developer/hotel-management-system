<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation</title>
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
                            ">
                                Booking Confirmed
                            </h2>

                            <p style="
                                margin: 0 0 16px;
                                font-size: 15px;
                                line-height: 1.7;
                                color: #5E6B5A;
                            ">
                                Dear <strong style="color: #1E2B22;">{{ $booking->guest->full_name }}</strong>,
                            </p>

                            <p style="
                                margin: 0 0 22px;
                                font-size: 14px;
                                line-height: 1.7;
                                color: #5E6B5A;
                            ">
                                Thank you for choosing&nbsp;
                                <strong style="color: #16261F;">Kumpuchea Otel</strong>.
                                Your booking has been successfully confirmed. Below are your booking details.
                            </p>

                            <!-- Booking Code Box -->
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
                                            Booking Code
                                        </div>

                                        <div style="
                                            font-family: Georgia, 'Times New Roman', serif;
                                            font-size: 28px;
                                            font-weight: 600;
                                            letter-spacing: 4px;
                                            color: #16261F;
                                        ">
                                            {{ $booking->booking_code }}
                                        </div>

                                    </td>
                                </tr>
                            </table>

                            <!-- Booking Details -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                   style="margin-top: 26px;">
                                <tr>
                                    <td style="
                                        background-color: #F1F3ED;
                                        border: 1px solid #DCE3D5;
                                        border-radius: 12px;
                                        padding: 24px 22px;
                                    ">

                                        <div style="
                                            font-size: 11px;
                                            color: #7A8677;
                                            text-transform: uppercase;
                                            letter-spacing: 2px;
                                            margin-bottom: 18px;
                                        ">
                                            Booking Information
                                        </div>

                                        <!-- Check-in -->
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                               style="margin-bottom: 14px;">
                                            <tr>
                                                <td style="font-size: 13px; color: #7A8677; width: 120px;">
                                                    Check-in
                                                </td>
                                                <td style="font-size: 14px; color: #1E2B22; font-weight: 600;">
                                                    {{ $booking->check_in->format('M d, Y') }}
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Check-out -->
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                               style="margin-bottom: 14px;">
                                            <tr>
                                                <td style="font-size: 13px; color: #7A8677; width: 120px;">
                                                    Check-out
                                                </td>
                                                <td style="font-size: 14px; color: #1E2B22; font-weight: 600;">
                                                    {{ $booking->check_out->format('M d, Y') }}
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Adults -->
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                               style="margin-bottom: 14px;">
                                            <tr>
                                                <td style="font-size: 13px; color: #7A8677; width: 120px;">
                                                    Adults
                                                </td>
                                                <td style="font-size: 14px; color: #1E2B22; font-weight: 600;">
                                                    {{ $booking->adults }}
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Children -->
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="font-size: 13px; color: #7A8677; width: 120px;">
                                                    Children
                                                </td>
                                                <td style="font-size: 14px; color: #1E2B22; font-weight: 600;">
                                                    {{ $booking->children }}
                                                </td>
                                            </tr>
                                        </table>

                                    </td>
                                </tr>
                            </table>

                            <!-- Room Details -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                   style="margin-top: 26px;">
                                <tr>
                                    <td style="
                                        background-color: #FFFFFF;
                                        border: 1px solid #DCE3D5;
                                        border-radius: 12px;
                                        padding: 24px 22px;
                                    ">

                                        <div style="
                                            font-size: 11px;
                                            color: #7A8677;
                                            text-transform: uppercase;
                                            letter-spacing: 2px;
                                            margin-bottom: 18px;
                                        ">
                                            Room Details
                                        </div>

                                        @foreach ($booking->bookingItems as $item)
                                            <div style="
                                                {{ !$loop->last ? 'padding-bottom: 16px; margin-bottom: 16px; border-bottom: 1px solid #DCE3D5;' : '' }}
                                            ">
                                                <div style="
                                                    font-size: 15px;
                                                    font-weight: 600;
                                                    color: #16261F;
                                                    margin-bottom: 8px;
                                                ">
                                                    {{ $item->room->roomType->name ?? 'Room' }}
                                                </div>

                                                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                                    <tr>
                                                        <td style="font-size: 13px; color: #7A8677; padding-bottom: 4px;">
                                                            Room {{ $item->room->room_number ?? $item->room->id }}
                                                        </td>
                                                        <td align="right" style="font-size: 13px; color: #5E6B5A; padding-bottom: 4px;">
                                                            {{ $item->nights }} night(s) &times; ${{ number_format((float) $item->price_per_night, 2) }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="2" align="right" style="
                                                            font-size: 14px;
                                                            font-weight: 600;
                                                            color: #1E2B22;
                                                            padding-top: 4px;
                                                        ">
                                                            ${{ number_format((float) $item->subtotal, 2) }}
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>
                                        @endforeach

                                    </td>
                                </tr>
                            </table>

                            <!-- Total Amount -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                   style="margin-top: 26px;">
                                <tr>
                                    <td align="right"
                                        style="
                                            padding: 22px 24px;
                                            background-color: #16261F;
                                            border-radius: 12px;
                                        ">
                                        <span style="
                                            font-size: 12px;
                                            color: #7FA35C;
                                            text-transform: uppercase;
                                            letter-spacing: 2px;
                                            margin-right: 12px;
                                        ">
                                            Total Amount
                                        </span>
                                        <span style="
                                            font-family: Georgia, 'Times New Roman', serif;
                                            font-size: 26px;
                                            font-weight: 600;
                                            color: #F2F5EC;
                                        ">
                                            ${{ number_format((float) $booking->total_amount, 2) }}
                                        </span>
                                    </td>
                                </tr>
                            </table>

                            @if ($booking->special_request)
                                <!-- Special Request -->
                                <table width="100%" cellpadding="0" cellspacing="0" border="0"
                                       style="margin-top: 26px;">
                                    <tr>
                                        <td style="
                                            background-color: #EEF1E9;
                                            border-left: 4px solid #7FA35C;
                                            border-radius: 8px;
                                            padding: 14px 16px;
                                        ">
                                            <div style="
                                                font-size: 11px;
                                                color: #7A8677;
                                                text-transform: uppercase;
                                                letter-spacing: 2px;
                                                margin-bottom: 8px;
                                            ">
                                                Special Request
                                            </div>
                                            <p style="
                                                margin: 0;
                                                font-size: 14px;
                                                line-height: 1.6;
                                                color: #5E6B5A;
                                            ">
                                                {{ $booking->special_request }}
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            <!-- Closing -->
                            <p style="
                                margin: 28px 0 0;
                                font-size: 14px;
                                line-height: 1.7;
                                color: #5E6B5A;
                            ">
                                We look forward to welcoming you.
                            </p>

                            <p style="
                                margin: 20px 0 0;
                                font-size: 14px;
                                line-height: 1.7;
                                color: #5E6B5A;
                            ">
                                Regards,<br>
                                <strong style="color: #16261F;">Kumpuchea Otel Team</strong>
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
                                &copy; {{ date('Y') }} Kumpuchea Otel. All rights reserved.
                            </p>

                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
