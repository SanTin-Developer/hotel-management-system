<?php

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Guest;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\Room\RoomService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function roomService(): RoomService
{
    return app(RoomService::class);
}

function createRoomTypeForServiceTest(): RoomType
{
    return RoomType::create([
        'name' => fake()->unique()->words(2, true),
        'capacity' => 2,
        'base_price' => 80,
        'status' => 'active',
    ]);
}

function createRoomForServiceTest(
    RoomType $roomType,
    string $roomNumber = '101'
): Room {
    return Room::create([
        'room_type_id' => $roomType->id,
        'room_number' => $roomNumber,
        'floor' => 1,
        'status' => 'available',
        'description' => 'Test room',
    ]);
}

it('gets all rooms with room type and booking item count', function () {
    $roomType = createRoomTypeForServiceTest();

    createRoomForServiceTest($roomType, '101');
    createRoomForServiceTest($roomType, '102');

    $rooms = roomService()->getAll();

    expect($rooms)->toHaveCount(2);
    expect($rooms->first()->roomType)->toBeInstanceOf(RoomType::class);
    expect($rooms->first()->booking_items_count)->toBe(0);
});

it('gets one room with its room type and booking items', function () {
    $roomType = createRoomTypeForServiceTest();

    $room = createRoomForServiceTest($roomType);

    $result = roomService()->getById($room);

    expect($result->id)->toBe($room->id);
    expect($result->roomType->id)->toBe($roomType->id);
    expect($result->bookingItems)->toBeEmpty();
    expect($result->booking_items_count)->toBe(0);
});

it('creates a room', function () {
    $roomType = createRoomTypeForServiceTest();

    $room = roomService()->create([
        'room_type_id' => $roomType->id,
        'room_number' => '201',
        'floor' => 2,
        'status' => 'available',
        'description' => 'New test room',
    ]);

    expect($room)->toBeInstanceOf(Room::class);
    expect($room->roomType->id)->toBe($roomType->id);

    $this->assertDatabaseHas('rooms', [
        'id' => $room->id,
        'room_type_id' => $roomType->id,
        'room_number' => '201',
        'floor' => 2,
        'status' => 'available',
    ]);
});

it('updates a room', function () {
    $roomType = createRoomTypeForServiceTest();

    $room = createRoomForServiceTest($roomType);

    $updated = roomService()->update($room, [
        'room_type_id' => $roomType->id,
        'room_number' => '202',
        'floor' => 2,
        'status' => 'maintenance',
        'description' => 'Updated room',
    ]);

    expect($updated->room_number)->toBe('202');
    expect($updated->floor)->toBe(2);
    expect($updated->status)->toBe('maintenance');
    expect($updated->roomType->id)->toBe($roomType->id);

    $this->assertDatabaseHas('rooms', [
        'id' => $room->id,
        'room_number' => '202',
        'floor' => 2,
        'status' => 'maintenance',
    ]);
});

it('deletes a room without booking items', function () {
    $roomType = createRoomTypeForServiceTest();

    $room = createRoomForServiceTest($roomType);

    roomService()->delete($room);

    $this->assertDatabaseMissing('rooms', [
        'id' => $room->id,
    ]);
});

it('cannot delete a room that has booking items', function () {
    $roomType = createRoomTypeForServiceTest();

    $room = createRoomForServiceTest($roomType);

    $guest = Guest::create([
        'full_name' => 'Test Guest',
        'email' => fake()->unique()->safeEmail(),
        'phone' => '012345678',
        'country' => 'Cambodia',
    ]);

    $booking = Booking::create([
        'booking_code' => 'TEST-'.fake()->unique()->numerify('#####'),
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
        'booking_id' => $booking->id,
        'room_id' => $room->id,
        'price_per_night' => 80,
        'nights' => 4,
        'subtotal' => 320,
        'status' => 'reserved',
    ]);

    expect(fn () => roomService()->delete($room))
        ->toThrow(QueryException::class);

    $this->assertDatabaseHas('rooms', [
        'id' => $room->id,
    ]);
});
