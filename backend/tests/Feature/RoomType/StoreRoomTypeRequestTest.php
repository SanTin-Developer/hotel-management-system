<?php

use App\Http\Requests\Api\V1\RoomType\StoreRoomTypeRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as LaravelValidator;

uses(RefreshDatabase::class);

function validateRoomType(array $data): LaravelValidator
{
    $request = new StoreRoomTypeRequest;

    return Validator::make(
        $data,
        $request->rules(),
        $request->messages()
    );
}

it('accepts valid room type data', function () {
    $validator = validateRoomType([
        'name' => 'Deluxe Room',
        'description' => 'A spacious deluxe room.',
        'capacity' => 2,
        'base_price' => 80.00,
        'size' => 35.50,
        'bed_type' => 'King Bed',
        'image_url' => 'https://example.com/room.jpg',
        'status' => 'active',
    ]);

    expect($validator->passes())->toBeTrue();
});

it('requires the room type name', function () {
    $validator = validateRoomType([
        'capacity' => 2,
        'base_price' => 80,
        'status' => 'active',
    ]);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('name'))->toBeTrue();
});

it('rejects zero or negative capacity', function () {
    $validator = validateRoomType([
        'name' => 'Deluxe Room',
        'capacity' => 0,
        'base_price' => 80,
        'status' => 'active',
    ]);

    expect($validator->fails())->toBeTrue();
});

it('rejects negative base price', function () {
    $validator = validateRoomType([
        'name' => 'Deluxe Room',
        'capacity' => 2,
        'base_price' => -10,
        'status' => 'active',
    ]);

    expect($validator->fails())->toBeTrue();
});

it('rejects invalid status', function () {
    $validator = validateRoomType([
        'name' => 'Deluxe Room',
        'capacity' => 2,
        'base_price' => 80,
        'status' => 'available',
    ]);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('status'))->toBeTrue();
});

it('rejects an invalid image url', function () {
    $validator = validateRoomType([
        'name' => 'Deluxe Room',
        'capacity' => 2,
        'base_price' => 80,
        'image_url' => 'not-a-url',
        'status' => 'active',
    ]);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('image_url'))->toBeTrue();
});

it('allows optional fields to be omitted', function () {
    $validator = validateRoomType([
        'name' => 'Standard Room',
        'capacity' => 2,
        'base_price' => 50,
        'status' => 'active',
    ]);

    expect($validator->passes())->toBeTrue();
});
