<?php

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Guest;
use App\Models\Room;
use App\Models\RoomType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function availabilityDate(int $offset): string
{
    return Carbon::today()->addDays($offset)->toDateString();
}

function availabilityRoomType(): RoomType
{
    return RoomType::create([
        'name' => fake()->unique()->words(2, true),
        'capacity' => 2,
        'base_price' => 80,
        'status' => 'active',
    ]);
}

function availabilityRoom(RoomType $roomType, string $number): Room
{
    return Room::create([
        'room_type_id' => $roomType->id,
        'room_number' => $number,
        'floor' => 1,
        'status' => 'available',
        'description' => 'Availability test room',
    ]);
}

function availabilityGuest(): Guest
{
    return Guest::create([
        'full_name' => 'Availability Guest',
        'email' => fake()->unique()->safeEmail(),
        'phone' => '012345678',
        'country' => 'Cambodia',
    ]);
}

it('returns available rooms', function () {
    $roomType = availabilityRoomType();

    $room1 = availabilityRoom($roomType, '101');
    $room2 = availabilityRoom($roomType, '102');

    $response = $this->getJson(
        '/api/v1/bookings/availability'
        .'?check_in='.availabilityDate(7)
        .'&check_out='.availabilityDate(10)
    );

    $response
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'room_number',
                    'status',
                ],
            ],
        ]);

    expect($response->json('data'))->toHaveCount(2);
});

it('excludes a room with an overlapping confirmed booking', function () {
    $roomType = availabilityRoomType();

    $room1 = availabilityRoom($roomType, '101');
    $room2 = availabilityRoom($roomType, '102');

    $booking = Booking::create([
        'booking_code' => 'AVAIL-0001',
        'guest_id' => availabilityGuest()->id,
        'check_in' => availabilityDate(8),
        'check_out' => availabilityDate(9),
        'adults' => 2,
        'children' => 0,
        'total_amount' => 160,
        'booking_source' => 'website',
        'status' => 'confirmed',
    ]);

    BookingItem::create([
        'booking_id' => $booking->id,
        'room_id' => $room1->id,
        'price_per_night' => 80,
        'nights' => 2,
        'subtotal' => 160,
        'status' => 'reserved',
    ]);

    $response = $this->getJson(
        '/api/v1/bookings/availability'
        .'?check_in='.availabilityDate(7)
        .'&check_out='.availabilityDate(10)
    );

    $response->assertOk();

    $roomNumbers = collect($response->json('data'))
        ->pluck('room_number')
        ->all();

    expect($roomNumbers)
        ->toContain('102')
        ->not->toContain('101');
});

it('allows a room when the requested check-in equals an existing check-out', function () {
    $roomType = availabilityRoomType();

    $room = availabilityRoom($roomType, '101');

    $booking = Booking::create([
        'booking_code' => 'AVAIL-0002',
        'guest_id' => availabilityGuest()->id,
        'check_in' => availabilityDate(7),
        'check_out' => availabilityDate(9),
        'adults' => 2,
        'children' => 0,
        'total_amount' => 320,
        'booking_source' => 'website',
        'status' => 'confirmed',
    ]);

    BookingItem::create([
        'booking_id' => $booking->id,
        'room_id' => $room->id,
        'price_per_night' => 80,
        'nights' => 4,
        'subtotal' => 320,
        'status' => 'reserved',
    ]);

    $response = $this->getJson(
        '/api/v1/bookings/availability'
        .'?check_in='.availabilityDate(9)
        .'&check_out='.availabilityDate(12)
    );

    $response->assertOk();

    $roomNumbers = collect($response->json('data'))
        ->pluck('room_number')
        ->all();

    expect($roomNumbers)->toContain('101');
});

it('ignores cancelled bookings when checking availability', function () {
    $roomType = availabilityRoomType();

    $room = availabilityRoom($roomType, '101');

    $booking = Booking::create([
        'booking_code' => 'AVAIL-0003',
        'guest_id' => availabilityGuest()->id,
        'check_in' => availabilityDate(8),
        'check_out' => availabilityDate(9),
        'adults' => 2,
        'children' => 0,
        'total_amount' => 160,
        'booking_source' => 'website',
        'status' => 'cancelled',
    ]);

    BookingItem::create([
        'booking_id' => $booking->id,
        'room_id' => $room->id,
        'price_per_night' => 80,
        'nights' => 2,
        'subtotal' => 160,
        'status' => 'reserved',
    ]);

    $response = $this->getJson(
        '/api/v1/bookings/availability'
        .'?check_in='.availabilityDate(7)
        .'&check_out='.availabilityDate(10)
    );

    $response->assertOk();

    $roomNumbers = collect($response->json('data'))
        ->pluck('room_number')
        ->all();

    expect($roomNumbers)->toContain('101');
});

it('rejects an invalid date range', function () {
    $response = $this->getJson(
        '/api/v1/bookings/availability'
        .'?check_in='.availabilityDate(8)
        .'&check_out='.availabilityDate(5)
    );

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.check_out.0',
            'Check-out must be after check-in.'
        );
});
