<?php

use App\Http\Requests\Api\V1\RoomType\UpdateRoomTypeRequest;
use App\Models\RoomType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as LaravelValidator;

uses(RefreshDatabase::class);

function validateUpdateRoomType(array $data, int $roomTypeId): LaravelValidator
{
    $request = UpdateRoomTypeRequest::create(
        '/api/v1/room-types/'.$roomTypeId,
        'PUT',
        $data
    );

    $request->setRouteResolver(function () use ($roomTypeId) {
        return new class($roomTypeId)
        {
            public function __construct(private int $roomTypeId) {}

            public function parameter(string $key): mixed
            {
                return $key === 'roomType'
                    ? $this->roomTypeId
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

it('allows a room type to keep its current name', function () {
    $roomType = RoomType::create([
        'name' => 'Deluxe Room',
        'capacity' => 2,
        'base_price' => 80,
        'status' => 'active',
    ]);

    $validator = validateUpdateRoomType([
        'name' => 'Deluxe Room',
        'capacity' => 3,
        'base_price' => 90,
        'status' => 'active',
    ], $roomType->id);

    expect($validator->passes())->toBeTrue();
});

it('rejects a name already used by another room type', function () {
    $roomType1 = RoomType::create([
        'name' => 'Deluxe Room',
        'capacity' => 2,
        'base_price' => 80,
        'status' => 'active',
    ]);

    $roomType2 = RoomType::create([
        'name' => 'Suite',
        'capacity' => 4,
        'base_price' => 150,
        'status' => 'active',
    ]);

    $validator = validateUpdateRoomType([
        'name' => 'Suite',
        'capacity' => 2,
        'base_price' => 90,
        'status' => 'active',
    ], $roomType1->id);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('name'))->toBeTrue();
});

it('accepts valid update data', function () {
    $roomType = RoomType::create([
        'name' => 'Standard Room',
        'capacity' => 2,
        'base_price' => 50,
        'status' => 'active',
    ]);

    $validator = validateUpdateRoomType([
        'name' => 'Premium Room',
        'description' => 'Updated description',
        'capacity' => 3,
        'base_price' => 120,
        'size' => 40,
        'bed_type' => 'King Bed',
        'image_url' => 'https://example.com/premium.jpg',
        'status' => 'active',
    ], $roomType->id);

    expect($validator->passes())->toBeTrue();
});
