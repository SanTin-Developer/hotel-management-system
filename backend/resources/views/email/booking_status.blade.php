<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Booking {{ ucfirst($newStatus) }}</title>
</head>

<body style="
    margin: 0;
    padding: 0;
    background-color: #f5f5f5;
    font-family: Arial, Helvetica, sans-serif;
">

<div style="
    max-width: 650px;
    margin: 40px auto;
    background: #ffffff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
">

    {{-- Header --}}
    <div style="
        padding: 30px;
        text-align: center;
        background-color: #1f2937;
        color: #ffffff;
    ">
        <h1 style="margin: 0;">
            Hotel Management System
        </h1>

        <p style="
            margin: 10px 0 0;
            font-size: 16px;
        ">
            Booking Update
        </p>
    </div>

    {{-- Content --}}
    <div style="padding: 30px;">

        <h2 style="margin-top: 0;">
            Dear {{ $booking->guest->full_name }},
        </h2>

        <p>
            The status of your booking has been updated.
        </p>

        {{-- Status badge --}}
        <div style="
            margin-top: 25px;
            padding: 20px;
            background-color: #f9fafb;
            border-radius: 8px;
            text-align: center;
        ">
            <p style="
                margin: 0;
                color: #6b7280;
                text-transform: uppercase;
                letter-spacing: 1px;
                font-size: 13px;
            ">
                New Status
            </p>

            <p style="
                margin: 10px 0 0;
                font-size: 26px;
                font-weight: bold;
                color: #1f2937;
            ">
                {{ ucfirst($newStatus) }}
            </p>
        </div>

        {{-- Booking information --}}
        <div style="
            margin-top: 25px;
            padding: 20px;
            background-color: #f9fafb;
            border-radius: 8px;
        ">
            <h3 style="margin-top: 0;">
                Booking Information
            </h3>

            <p>
                <strong>Booking Code:</strong>
                {{ $booking->booking_code }}
            </p>

            <p>
                <strong>Check-in:</strong>
                {{ $booking->check_in->format('M d, Y') }}
            </p>

            <p>
                <strong>Check-out:</strong>
                {{ $booking->check_out->format('M d, Y') }}
            </p>

            <p>
                <strong>Total Amount:</strong>
                ${{ number_format((float) $booking->total_amount, 2) }}
            </p>
        </div>

        @if ($note)

            <div style="margin-top: 25px; padding: 20px; background-color: #fffbeb; border-radius: 8px;">

                <h3 style="margin-top: 0; color: #92400e;">
                    Note
                </h3>

                <p style="margin: 0; color: #78350f;">
                    {{ $note }}
                </p>

            </div>

        @endif

        <p style="margin-top: 30px;">
            If you have any questions, please contact our support team.
        </p>

        <p>
            Best regards,<br>
            <strong>Kampuchea Otel Team</strong>
        </p>

    </div>

    {{-- Footer --}}
    <div style="
        padding: 20px;
        text-align: center;
        background-color: #f3f4f6;
        color: #6b7280;
        font-size: 13px;
    ">

        This is an automated email.
        Please do not reply directly to this message.

    </div>

</div>

</body>
</html>
