<?php

use App\Http\Requests\Api\V1\Booking\StoreBookingRequest;
use App\Models\Guest;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as LaravelValidator;

uses(RefreshDatabase::class);

function validateStoreBooking(array $data): LaravelValidator
{
    $request = new StoreBookingRequest;

    return Validator::make(
        $data,
        $request->rules(),
        $request->messages()
    );
}

function bookingRequestGuest(): Guest
{
    return Guest::create([
        'full_name' => 'Booking Request Guest',
        'email' => fake()->unique()->safeEmail(),
        'phone' => '012345678',
        'country' => 'Cambodia',
    ]);
}

function bookingTestRoom(): Room
{
    $roomType = RoomType::create([
        'name' => fake()->unique()->words(2, true),
        'capacity' => 2,
        'base_price' => 80,
        'status' => 'active',
    ]);

    return Room::create([
        'room_type_id' => $roomType->id,
        'room_number' => fake()->unique()->numerify('###'),
        'floor' => 1,
        'status' => 'available',
    ]);
}

it('accepts valid booking data', function () {
    $room = bookingTestRoom();
    $guest = bookingRequestGuest();

    $validator = validateStoreBooking([
        'guest_id' => $guest->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-13',
        'adults' => 2,
        'children' => 1,
        'room_ids' => [$room->id],
        'special_request' => 'Late check-in',
    ]);

    expect($validator->passes())->toBeTrue();
});

it('requires at least one room', function () {
    $validator = validateStoreBooking([
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-13',
        'adults' => 2,
        'children' => 0,
        'room_ids' => [],
    ]);

    expect($validator->fails())->toBeTrue();

    expect($validator->errors()->has('room_ids'))->toBeTrue();
});

it('rejects duplicate room ids', function () {
    $room = bookingTestRoom();

    $validator = validateStoreBooking([
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-13',
        'adults' => 2,
        'children' => 0,
        'room_ids' => [
            $room->id,
            $room->id,
        ],
    ]);

    expect($validator->fails())->toBeTrue();

    expect(
        $validator->errors()->get('room_ids.1')
    )->toContain('Duplicate room IDs are not allowed.');
});

it('rejects a non existing room', function () {
    $validator = validateStoreBooking([
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-13',
        'adults' => 2,
        'children' => 0,
        'room_ids' => [999999],
    ]);

    expect($validator->fails())->toBeTrue();

    expect(
        $validator->errors()->get('room_ids.0')
    )->toContain('One or more selected rooms do not exist.');
});

it('rejects an invalid date range', function () {
    $validator = validateStoreBooking([
        'check_in' => '2026-09-13',
        'check_out' => '2026-09-10',
        'adults' => 2,
        'children' => 0,
        'room_ids' => [1],
    ]);

    expect($validator->fails())->toBeTrue();

    expect(
        $validator->errors()->get('check_out')
    )->toContain('Check-out must be after check-in.');
});

it('rejects zero adults', function () {
    $validator = validateStoreBooking([
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-13',
        'adults' => 0,
        'children' => 1,
        'room_ids' => [1],
    ]);

    expect($validator->fails())->toBeTrue();

    expect(
        $validator->errors()->get('adults')
    )->toContain('At least one adult is required.');
});

it('accepts zero children', function () {
    $room = bookingTestRoom();
    $guest = bookingRequestGuest();

    $validator = validateStoreBooking([
        'guest_id' => $guest->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-13',
        'adults' => 2,
        'children' => 0,
        'room_ids' => [$room->id],
    ]);

    expect($validator->passes())->toBeTrue();
});
