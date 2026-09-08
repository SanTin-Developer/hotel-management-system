<?php

use App\Jobs\DeleteCancelledBookings;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingStatusHistory;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function cleanupGuest(): Guest
{
    return Guest::create([
        'full_name' => 'Cleanup Guest',
        'email' => fake()->unique()->safeEmail(),
        'phone' => '012345678',
        'country' => 'Cambodia',
    ]);
}

function cleanupRoom(string $roomNumber = '555'): Room
{
    $roomType = RoomType::create([
        'name' => 'Deluxe Cleanup',
        'capacity' => 2,
        'base_price' => 80,
        'image_url' => null,
        'description' => 'Test room type',
    ]);

    return Room::create([
        'room_number' => $roomNumber,
        'room_type_id' => $roomType->id,
        'floor' => 5,
        'status' => 'available',
        'price_per_night' => 80,
    ]);
}

function cleanupCancelledBooking(Guest $guest, Room $room, int $daysAgo): Booking
{
    $booking = Booking::create([
        'booking_code' => 'CLN-'.fake()->unique()->numerify('#####'),
        'guest_id' => $guest->id,
        'check_in' => now()->subDays(10)->toDateString(),
        'check_out' => now()->subDays(7)->toDateString(),
        'adults' => 2,
        'children' => 0,
        'total_amount' => 240,
        'deposit_rate' => 20,
        'deposit_amount' => 48,
        'booking_source' => 'website',
        'status' => 'cancelled',
    ]);

    $booking->forceFill([
        'created_at' => now()->subDays($daysAgo + 1),
        'updated_at' => now()->subDays($daysAgo),
    ])->save();

    BookingItem::create([
        'booking_id' => $booking->id,
        'room_id' => $room->id,
        'price_per_night' => 80,
        'nights' => 3,
        'subtotal' => 240,
        'status' => 'reserved',
    ]);

    BookingStatusHistory::create([
        'booking_id' => $booking->id,
        'status' => 'cancelled',
        'note' => 'Cancelled for cleanup test.',
        'created_at' => now()->subDays($daysAgo),
    ]);

    Payment::create([
        'booking_id' => $booking->id,
        'amount' => 48,
        'payment_method' => 'aba',
        'transaction_id' => 'RFX-'.fake()->unique()->numerify('#######'),
        'status' => 'refunded',
        'paid_at' => now()->subDays($daysAgo + 1),
    ]);

    return $booking;
}

it('deletes cancelled bookings older than 7 days with their related records', function () {
    $guest = cleanupGuest();
    $room = cleanupRoom();

    $old = cleanupCancelledBooking($guest, $room, 8);
    $recent = cleanupCancelledBooking($guest, $room, 6);
    $active = Booking::create([
        'booking_code' => 'ACT-'.fake()->unique()->numerify('#####'),
        'guest_id' => $guest->id,
        'check_in' => now()->addDays(3)->toDateString(),
        'check_out' => now()->addDays(5)->toDateString(),
        'adults' => 1,
        'children' => 0,
        'total_amount' => 160,
        'deposit_rate' => 20,
        'deposit_amount' => 32,
        'booking_source' => 'website',
        'status' => 'confirmed',
    ]);

    $active->forceFill([
        'created_at' => now()->subDays(9),
        'updated_at' => now()->subDays(8),
    ])->save();

    (new DeleteCancelledBookings())->handle();

    $this->assertDatabaseMissing('bookings', ['id' => $old->id]);
    $this->assertDatabaseMissing('booking_items', ['booking_id' => $old->id]);
    $this->assertDatabaseMissing('booking_status_histories', ['booking_id' => $old->id]);
    $this->assertDatabaseMissing('payments', ['booking_id' => $old->id]);

    $this->assertDatabaseHas('bookings', ['id' => $recent->id]);
    $this->assertDatabaseHas('booking_items', ['booking_id' => $recent->id]);
    $this->assertDatabaseHas('payments', ['booking_id' => $recent->id]);

    $this->assertDatabaseHas('bookings', ['id' => $active->id]);
});

it('deletes nothing when there are no expired cancelled bookings', function () {
    $guest = cleanupGuest();
    $room = cleanupRoom();

    $recent = cleanupCancelledBooking($guest, $room, 1);

    (new DeleteCancelledBookings())->handle();

    $this->assertDatabaseHas('bookings', ['id' => $recent->id]);
});