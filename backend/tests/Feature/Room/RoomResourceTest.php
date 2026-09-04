<?php

use App\Http\Resources\Api\V1\RoomResource;
use App\Http\Resources\Api\V1\RoomTypeResource;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

function createRoomTypeForResourceTest(): RoomType
{
    return RoomType::create([
        'name' => 'Deluxe Room',
        'description' => 'A spacious deluxe room.',
        'capacity' => 2,
        'base_price' => 80,
        'size' => 35.50,
        'bed_type' => 'King Bed',
        'status' => 'active',
    ]);
}

function createRoomForResourceTest(RoomType $roomType): Room
{
    return Room::create([
        'room_type_id' => $roomType->id,
        'room_number' => '101',
        'floor' => 1,
        'status' => 'available',
        'description' => 'First floor deluxe room.',
    ]);
}

it('transforms a room into the expected API structure', function () {
    $roomType = createRoomTypeForResourceTest();
    $room = createRoomForResourceTest($roomType);

    $request = Request::create('/api/v1/rooms', 'GET');

    $resource = (new RoomResource($room))
        ->toArray($request);

    expect($resource)->toMatchArray([
        'id' => $room->id,
        'room_type_id' => $roomType->id,
        'room_number' => '101',
        'floor' => 1,
        'status' => 'available',
        'description' => 'First floor deluxe room.',
    ]);

    expect($resource)->toHaveKeys([
        'id',
        'room_type_id',
        'room_number',
        'floor',
        'status',
        'description',
        'created_at',
        'updated_at',
    ]);
});

it('includes the room type when it is loaded', function () {
    $roomType = createRoomTypeForResourceTest();

    $room = createRoomForResourceTest($roomType)
        ->load('roomType');

    $request = Request::create('/api/v1/rooms', 'GET');

    $resource = (new RoomResource($room))
        ->toArray($request);

    expect($resource)->toHaveKey('room_type');

    $roomTypeResource = $resource['room_type'];

    expect($roomTypeResource)->toBeInstanceOf(
        RoomTypeResource::class
    );

    $roomTypeData = $roomTypeResource->toArray($request);

    expect($roomTypeData)->toMatchArray([
        'id' => $roomType->id,
        'name' => 'Deluxe Room',
        'capacity' => 2,
        'status' => 'active',
    ]);
});

it('includes booking item count when loaded', function () {
    $roomType = createRoomTypeForResourceTest();

    $room = createRoomForResourceTest($roomType)
        ->loadCount('bookingItems');

    $request = Request::create('/api/v1/rooms', 'GET');

    $resource = (new RoomResource($room))
        ->toArray($request);

    expect($resource)->toHaveKey('booking_items_count');
    expect($resource['booking_items_count'])->toBe(0);
});

it('does not expose unexpected database fields', function () {
    $roomType = createRoomTypeForResourceTest();
    $room = createRoomForResourceTest($roomType);

    $request = Request::create('/api/v1/rooms', 'GET');

    $resource = (new RoomResource($room))
        ->toArray($request);

    expect($resource)->not->toHaveKeys([
        'password',
        'remember_token',
        'deleted_at',
    ]);
});
