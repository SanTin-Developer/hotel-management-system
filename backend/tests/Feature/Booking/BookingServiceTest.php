<?php

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Guest;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\Booking\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function bookingService(): BookingService
{
    return app(BookingService::class);
}

function createBookingGuest(): Guest
{
    return Guest::create([
        'full_name' => 'Booking Test Guest',
        'email' => fake()->unique()->safeEmail(),
        'phone' => '012345678',
        'country' => 'Cambodia',
    ]);
}

function createBookingRoom(
    string $roomNumber = '101',
    float $basePrice = 80
): Room {
    $roomType = RoomType::create([
        'name' => fake()->unique()->words(2, true),
        'capacity' => 2,
        'base_price' => $basePrice,
        'status' => 'active',
    ]);

    return Room::create([
        'room_type_id' => $roomType->id,
        'room_number' => $roomNumber,
        'floor' => 1,
        'status' => 'available',
        'description' => 'Booking test room',
    ]);
}

function bookingPayload(
    Guest $guest,
    array $roomIds,
    string $checkIn = '2026-09-10',
    string $checkOut = '2026-09-13'
): array {
    return [
        'guest_id' => $guest->id,
        'check_in' => $checkIn,
        'check_out' => $checkOut,
        'adults' => 2,
        'children' => 1,
        'room_ids' => $roomIds,
        'special_request' => 'Late check-in',
    ];
}

it('creates a booking and its booking items', function () {
    $guest = createBookingGuest();
    $room = createBookingRoom('101', 80);

    $booking = bookingService()->create(
        bookingPayload($guest, [$room->id])
    );

    expect($booking)->toBeInstanceOf(Booking::class);
    expect($booking->guest_id)->toBe($guest->id);
    expect($booking->status)->toBe('pending');
    expect($booking->check_in->toDateString())->toBe('2026-09-10');
    expect($booking->check_out->toDateString())->toBe('2026-09-13');

    // 3 nights × 80
    expect((float) $booking->total_amount)->toBe(240.0);

    expect($booking->bookingItems)->toHaveCount(1);

    $item = $booking->bookingItems->first();

    expect($item->room_id)->toBe($room->id);
    expect((float) $item->price_per_night)->toBe(80.0);
    expect($item->nights)->toBe(3);
    expect((float) $item->subtotal)->toBe(240.0);

    $this->assertDatabaseHas('bookings', [
        'id' => $booking->id,
        'guest_id' => $guest->id,
        'status' => 'pending',
    ]);

    $this->assertDatabaseHas('booking_items', [
        'booking_id' => $booking->id,
        'room_id' => $room->id,
    ]);
});

it('calculates the total for multiple rooms', function () {
    $guest = createBookingGuest();

    $room1 = createBookingRoom('101', 80);
    $room2 = createBookingRoom('102', 120);

    $booking = bookingService()->create(
        bookingPayload($guest, [
            $room1->id,
            $room2->id,
        ])
    );

    // (80 + 120) × 3 nights
    expect((float) $booking->total_amount)->toBe(600.0);

    expect($booking->bookingItems)->toHaveCount(2);
});

it('rejects a room that is not currently available', function () {
    $guest = createBookingGuest();
    $room = createBookingRoom('101');

    $room->update([
        'status' => 'maintenance',
    ]);

    expect(fn () => bookingService()->create(
        bookingPayload($guest, [$room->id])
    ))->toThrow(ValidationException::class);

    $this->assertDatabaseCount('bookings', 0);
    $this->assertDatabaseCount('booking_items', 0);
});

it('rejects a room with an overlapping confirmed booking', function () {
    $guest = createBookingGuest();
    $room = createBookingRoom('101');

    $existingBooking = Booking::create([
        'booking_code' => 'EXISTING-001',
        'guest_id' => $guest->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-13',
        'adults' => 2,
        'children' => 0,
        'total_amount' => 240,
        'booking_source' => 'website',
        'status' => 'confirmed',
    ]);

    BookingItem::create([
        'booking_id' => $existingBooking->id,
        'room_id' => $room->id,
        'price_per_night' => 80,
        'nights' => 3,
        'subtotal' => 240,
        'status' => 'reserved',
    ]);

    $newGuest = createBookingGuest();

    expect(fn () => bookingService()->create(
        bookingPayload($newGuest, [$room->id])
    ))->toThrow(ValidationException::class);

    expect(Booking::count())->toBe(1);
    expect(BookingItem::count())->toBe(1);
});

it('allows an adjacent booking when check-in equals previous check-out', function () {
    $guest = createBookingGuest();
    $room = createBookingRoom('101');

    $existingBooking = Booking::create([
        'booking_code' => 'EXISTING-002',
        'guest_id' => $guest->id,
        'check_in' => '2026-09-01',
        'check_out' => '2026-09-05',
        'adults' => 2,
        'children' => 0,
        'total_amount' => 320,
        'booking_source' => 'website',
        'status' => 'confirmed',
    ]);

    BookingItem::create([
        'booking_id' => $existingBooking->id,
        'room_id' => $room->id,
        'price_per_night' => 80,
        'nights' => 4,
        'subtotal' => 320,
        'status' => 'reserved',
    ]);

    $newGuest = createBookingGuest();

    $booking = bookingService()->create(
        bookingPayload(
            $newGuest,
            [$room->id],
            '2026-09-05',
            '2026-09-08'
        )
    );

    expect($booking->id)->not->toBe($existingBooking->id);

    expect(Booking::count())->toBe(2);
    expect(BookingItem::count())->toBe(2);
});

it('ignores cancelled bookings when checking availability', function () {
    $guest = createBookingGuest();
    $room = createBookingRoom('101');

    $cancelledBooking = Booking::create([
        'booking_code' => 'CANCELLED-001',
        'guest_id' => $guest->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-13',
        'adults' => 2,
        'children' => 0,
        'total_amount' => 240,
        'booking_source' => 'website',
        'status' => 'cancelled',
    ]);

    BookingItem::create([
        'booking_id' => $cancelledBooking->id,
        'room_id' => $room->id,
        'price_per_night' => 80,
        'nights' => 3,
        'subtotal' => 240,
        'status' => 'reserved',
    ]);

    $newGuest = createBookingGuest();

    $booking = bookingService()->create(
        bookingPayload($newGuest, [$room->id])
    );

    expect($booking)->toBeInstanceOf(Booking::class);
    expect(Booking::count())->toBe(2);
});

it('generates a unique booking code', function () {
    $guest = createBookingGuest();

    $room1 = createBookingRoom('101');
    $room2 = createBookingRoom('102');

    $booking1 = bookingService()->create(
        bookingPayload($guest, [$room1->id])
    );

    $booking2 = bookingService()->create(
        bookingPayload($guest, [$room2->id])
    );

    expect($booking1->booking_code)->not->toBe($booking2->booking_code);
    expect($booking1->booking_code)->toStartWith('BK-');
    expect($booking2->booking_code)->toStartWith('BK-');
});
