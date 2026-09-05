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
            Booking Confirmation
        </p>
    </div>

    {{-- Content --}}
    <div style="padding: 30px;">

        <h2 style="margin-top: 0;">
            Dear {{ $booking->guest->full_name }},
        </h2>

        <p>
            Thank you for choosing our hotel.
            Your booking has been successfully confirmed.
        </p>

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
                <strong>Adults:</strong>
                {{ $booking->adults }}
            </p>

            <p>
                <strong>Children:</strong>
                {{ $booking->children }}
            </p>

        </div>

        {{-- Rooms --}}
        <div style="margin-top: 30px;">

            <h3>Room Details</h3>

            @foreach ($booking->bookingItems as $item)

                <div style="
                    padding: 15px 0;
                    border-bottom: 1px solid #e5e7eb;
                ">

                    <strong>
                        {{ $item->room->roomType->name ?? 'Room' }}
                    </strong>

                    <p style="margin: 8px 0;">
                        Room:
                        {{ $item->room->room_number ?? $item->room->id }}
                    </p>

                    <p style="margin: 8px 0;">
                        {{ $item->nights }} night(s)
                        ×
                        ${{ number_format((float) $item->price_per_night, 2) }}
                    </p>

                    <p style="margin: 8px 0;">
                        <strong>
                            Subtotal:
                            ${{ number_format((float) $item->subtotal, 2) }}
                        </strong>
                    </p>

                </div>

            @endforeach

        </div>

        {{-- Total --}}
        <div style="
            margin-top: 30px;
            padding: 20px;
            text-align: right;
            background-color: #f9fafb;
            border-radius: 8px;
        ">

            <span style="font-size: 18px;">
                Total Amount:
            </span>

            <strong style="font-size: 24px;">
                ${{ number_format((float) $booking->total_amount, 2) }}
            </strong>

        </div>

        @if ($booking->special_request)

            <div style="margin-top: 25px;">

                <h3>Special Request</h3>

                <p>
                    {{ $booking->special_request }}
                </p>

            </div>

        @endif

        <p style="margin-top: 30px;">
            We look forward to welcoming you.
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
