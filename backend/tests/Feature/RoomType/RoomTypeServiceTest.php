<?php

use App\Models\Room;
use App\Models\RoomType;
use App\Services\RoomTypeService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function roomTypeService(): RoomTypeService
{
    return app(RoomTypeService::class);
}

it('gets all room types ordered by name with rooms count', function () {
    RoomType::create([
        'name' => 'Suite',
        'capacity' => 4,
        'base_price' => 150,
        'status' => 'active',
    ]);

    RoomType::create([
        'name' => 'Deluxe Room',
        'capacity' => 2,
        'base_price' => 80,
        'status' => 'active',
    ]);

    $roomTypes = roomTypeService()->getAll();

    expect($roomTypes)->toHaveCount(2);
    expect($roomTypes->first()->name)->toBe('Deluxe Room');
    expect($roomTypes->first()->rooms_count)->toBe(0);
});

it('gets one room type with rooms count', function () {
    $roomType = RoomType::create([
        'name' => 'Deluxe Room',
        'capacity' => 2,
        'base_price' => 80,
        'status' => 'active',
    ]);

    $result = roomTypeService()->getById($roomType);

    expect($result->id)->toBe($roomType->id);
    expect($result->rooms_count)->toBe(0);
});

it('creates a room type', function () {
    $roomType = roomTypeService()->create([
        'name' => 'Premium Room',
        'description' => 'Premium room',
        'capacity' => 3,
        'base_price' => 120,
        'size' => 40,
        'bed_type' => 'King Bed',
        'image_url' => 'https://example.com/premium.jpg',
        'status' => 'active',
    ]);

    expect($roomType)->toBeInstanceOf(RoomType::class);

    $this->assertDatabaseHas('room_types', [
        'id' => $roomType->id,
        'name' => 'Premium Room',
        'capacity' => 3,
        'status' => 'active',
    ]);
});

it('updates a room type', function () {
    $roomType = RoomType::create([
        'name' => 'Standard Room',
        'capacity' => 2,
        'base_price' => 50,
        'status' => 'active',
    ]);

    $updated = roomTypeService()->update($roomType, [
        'name' => 'Updated Standard Room',
        'capacity' => 3,
        'base_price' => 65,
        'status' => 'inactive',
    ]);

    expect($updated->name)->toBe('Updated Standard Room');
    expect($updated->capacity)->toBe(3);

    $this->assertDatabaseHas('room_types', [
        'id' => $roomType->id,
        'name' => 'Updated Standard Room',
        'capacity' => 3,
        'base_price' => '65.00',
        'status' => 'inactive',
    ]);
});

it('deletes a room type without rooms', function () {
    $roomType = RoomType::create([
        'name' => 'Temporary Room',
        'capacity' => 2,
        'base_price' => 40,
        'status' => 'active',
    ]);

    roomTypeService()->delete($roomType);

    $this->assertDatabaseMissing('room_types', [
        'id' => $roomType->id,
    ]);
});

it('cannot delete a room type that has rooms', function () {
    $roomType = RoomType::create([
        'name' => 'Deluxe Room',
        'capacity' => 2,
        'base_price' => 80,
        'status' => 'active',
    ]);

    Room::create([
        'room_type_id' => $roomType->id,
        'room_number' => 'TEST-101',
        'floor' => 1,
        'status' => 'available',
        'description' => 'Test room',
    ]);

    expect(fn () => roomTypeService()->delete($roomType))
        ->toThrow(QueryException::class);

    $this->assertDatabaseHas('room_types', [
        'id' => $roomType->id,
    ]);
});
