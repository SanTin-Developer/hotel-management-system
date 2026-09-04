<?php

use App\Http\Resources\Api\V1\RoomTypeResource;
use App\Models\RoomType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

it('transforms a room type into the expected API structure', function () {
    $roomType = RoomType::create([
        'name' => 'Deluxe Room',
        'description' => 'A spacious deluxe room.',
        'capacity' => 2,
        'base_price' => 80.00,
        'size' => 35.50,
        'bed_type' => 'King Bed',
        'image_url' => 'https://example.com/deluxe.jpg',
        'status' => 'active',
    ]);

    $request = Request::create('/api/v1/room-types', 'GET');

    $resource = (new RoomTypeResource($roomType))
        ->toArray($request);

    expect($resource)->toMatchArray([
        'id' => $roomType->id,
        'name' => 'Deluxe Room',
        'description' => 'A spacious deluxe room.',
        'capacity' => 2,
        'base_price' => '80.00',
        'size' => '35.50',
        'bed_type' => 'King Bed',
        'image_url' => 'https://example.com/deluxe.jpg',
        'status' => 'active',
    ]);

    expect($resource)->toHaveKeys([
        'id',
        'name',
        'description',
        'capacity',
        'base_price',
        'size',
        'bed_type',
        'image_url',
        'status',
        'created_at',
        'updated_at',
    ]);
});

it('includes rooms count when rooms count is loaded', function () {
    $roomType = RoomType::create([
        'name' => 'Suite',
        'capacity' => 4,
        'base_price' => 150.00,
        'status' => 'active',
    ]);

    $roomType->loadCount('rooms');

    $request = Request::create('/api/v1/room-types', 'GET');

    $resource = (new RoomTypeResource($roomType))
        ->toArray($request);

    expect($resource)->toHaveKey('rooms_count');
    expect($resource['rooms_count'])->toBe(0);
});

it('does not expose unexpected database fields', function () {
    $roomType = RoomType::create([
        'name' => 'Standard Room',
        'capacity' => 2,
        'base_price' => 50.00,
        'status' => 'active',
    ]);

    $request = Request::create('/api/v1/room-types', 'GET');

    $resource = (new RoomTypeResource($roomType))
        ->toArray($request);

    expect($resource)->not->toHaveKeys([
        'password',
        'remember_token',
        'deleted_at',
    ]);
});
