<?php

use App\Http\Requests\Api\V1\Room\UpdateRoomRequest;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as LaravelValidator;

uses(RefreshDatabase::class);

function validateUpdateRoom(array $data, Room $room): LaravelValidator
{
    $request = UpdateRoomRequest::create(
        "/api/v1/rooms/{$room->id}",
        'PUT',
        $data
    );

    $request->setRouteResolver(function () use ($room) {
        return new class($room)
        {
            public function __construct(
                private Room $room
            ) {}

            public function parameter(string $key): mixed
            {
                return $key === 'room'
                    ? $this->room
                    : null;
            }
        };
    });

    return Validator::make(
        $data,
        $request->rules(),
        $request->messages()
    );
}

function makeRoomType(): RoomType
{
    return RoomType::create([
        'name' => fake()->unique()->words(2, true),
        'capacity' => 2,
        'base_price' => 80,
        'status' => 'active',
    ]);
}

function makeRoom(RoomType $roomType, string $roomNumber): Room
{
    return Room::create([
        'room_type_id' => $roomType->id,
        'room_number' => $roomNumber,
        'floor' => 1,
        'status' => 'available',
        'description' => 'Test room',
    ]);
}

it('accepts valid room update data', function () {
    $roomType = makeRoomType();
    $room = makeRoom($roomType, '101');

    $validator = validateUpdateRoom([
        'room_type_id' => $roomType->id,
        'room_number' => '101',
        'floor' => 2,
        'status' => 'maintenance',
        'description' => 'Updated room',
    ], $room);

    expect($validator->passes())->toBeTrue();
});

it('allows a room to keep its current room number', function () {
    $roomType = makeRoomType();
    $room = makeRoom($roomType, '101');

    $validator = validateUpdateRoom([
        'room_type_id' => $roomType->id,
        'room_number' => '101',
        'floor' => 1,
        'status' => 'available',
    ], $room);

    expect($validator->passes())->toBeTrue();
});

it('rejects a room number already used by another room', function () {
    $roomType = makeRoomType();

    $room1 = makeRoom($roomType, '101');
    makeRoom($roomType, '102');

    $validator = validateUpdateRoom([
        'room_type_id' => $roomType->id,
        'room_number' => '102',
        'floor' => 1,
        'status' => 'available',
    ], $room1);

    expect($validator->fails())->toBeTrue();

    expect(
        $validator->errors()->get('room_number')
    )->toContain('This room number is already in use.');
});

it('rejects a non existing room type', function () {
    $roomType = makeRoomType();
    $room = makeRoom($roomType, '101');

    $validator = validateUpdateRoom([
        'room_type_id' => 999999,
        'room_number' => '101',
        'floor' => 1,
        'status' => 'available',
    ], $room);

    expect($validator->fails())->toBeTrue();

    expect(
        $validator->errors()->get('room_type_id')
    )->toContain('The selected room type does not exist.');
});

it('rejects an invalid floor', function () {
    $roomType = makeRoomType();
    $room = makeRoom($roomType, '101');

    $validator = validateUpdateRoom([
        'room_type_id' => $roomType->id,
        'room_number' => '101',
        'floor' => 0,
        'status' => 'available',
    ], $room);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('floor'))->toBeTrue();
});

it('rejects an invalid room status', function () {
    $roomType = makeRoomType();
    $room = makeRoom($roomType, '101');

    $validator = validateUpdateRoom([
        'room_type_id' => $roomType->id,
        'room_number' => '101',
        'floor' => 1,
        'status' => 'reserved',
    ], $room);

    expect($validator->fails())->toBeTrue();

    expect(
        $validator->errors()->get('status')
    )->toContain('Invalid room status.');
});

it('allows description to be omitted', function () {
    $roomType = makeRoomType();
    $room = makeRoom($roomType, '101');

    $validator = validateUpdateRoom([
        'room_type_id' => $roomType->id,
        'room_number' => '101',
        'floor' => 1,
        'status' => 'available',
    ], $room);

    expect($validator->passes())->toBeTrue();
});
