<?php

use App\Http\Requests\Api\V1\Room\StoreRoomRequest;
use App\Models\RoomType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as LaravelValidator;

uses(RefreshDatabase::class);

function validateStoreRoom(array $data): LaravelValidator
{
    $request = new StoreRoomRequest;

    return Validator::make(
        $data,
        $request->rules(),
        $request->messages()
    );
}

it('accepts valid room data', function () {
    $roomType = RoomType::create([
        'name' => 'Deluxe Room',
        'capacity' => 2,
        'base_price' => 80,
        'status' => 'active',
    ]);

    $validator = validateStoreRoom([
        'room_type_id' => $roomType->id,
        'room_number' => '101',
        'floor' => 1,
        'status' => 'available',
        'description' => 'Deluxe room on first floor.',
    ]);

    expect($validator->passes())->toBeTrue();
});

it('requires a room type', function () {
    $validator = validateStoreRoom([
        'room_number' => '101',
        'floor' => 1,
        'status' => 'available',
    ]);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('room_type_id'))->toBeTrue();
});

it('rejects a non existing room type', function () {
    $validator = validateStoreRoom([
        'room_type_id' => 999999,
        'room_number' => '101',
        'floor' => 1,
        'status' => 'available',
    ]);

    expect($validator->fails())->toBeTrue();

    expect(
        $validator->errors()->get('room_type_id')
    )->toContain('The selected room type does not exist.');
});

it('requires a room number', function () {
    $roomType = RoomType::create([
        'name' => 'Standard Room',
        'capacity' => 2,
        'base_price' => 50,
        'status' => 'active',
    ]);

    $validator = validateStoreRoom([
        'room_type_id' => $roomType->id,
        'floor' => 1,
        'status' => 'available',
    ]);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('room_number'))->toBeTrue();
});

it('rejects an invalid floor', function () {
    $roomType = RoomType::create([
        'name' => 'Standard Room',
        'capacity' => 2,
        'base_price' => 50,
        'status' => 'active',
    ]);

    $validator = validateStoreRoom([
        'room_type_id' => $roomType->id,
        'room_number' => '101',
        'floor' => 0,
        'status' => 'available',
    ]);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('floor'))->toBeTrue();
});

it('rejects an invalid room status', function () {
    $roomType = RoomType::create([
        'name' => 'Standard Room',
        'capacity' => 2,
        'base_price' => 50,
        'status' => 'active',
    ]);

    $validator = validateStoreRoom([
        'room_type_id' => $roomType->id,
        'room_number' => '101',
        'floor' => 1,
        'status' => 'reserved',
    ]);

    expect($validator->fails())->toBeTrue();

    expect(
        $validator->errors()->get('status')
    )->toContain('Invalid room status.');
});

it('accepts every supported room status', function (string $status) {
    $roomType = RoomType::create([
        'name' => 'Standard Room',
        'capacity' => 2,
        'base_price' => 50,
        'status' => 'active',
    ]);

    $validator = validateStoreRoom([
        'room_type_id' => $roomType->id,
        'room_number' => '101',
        'floor' => 1,
        'status' => $status,
    ]);

    expect($validator->passes())->toBeTrue();
})->with([
    'available',
    'occupied',
    'maintenance',
    'cleaning',
    'out_of_service',
]);

it('allows description to be omitted', function () {
    $roomType = RoomType::create([
        'name' => 'Suite',
        'capacity' => 4,
        'base_price' => 150,
        'status' => 'active',
    ]);

    $validator = validateStoreRoom([
        'room_type_id' => $roomType->id,
        'room_number' => '201',
        'floor' => 2,
        'status' => 'available',
    ]);

    expect($validator->passes())->toBeTrue();
});
