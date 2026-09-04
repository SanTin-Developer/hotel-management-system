<?php

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Guest;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::insert([
        ['name' => 'rooms.view', 'guard_name' => 'web'],
        ['name' => 'rooms.create', 'guard_name' => 'web'],
        ['name' => 'rooms.update', 'guard_name' => 'web'],
        ['name' => 'rooms.delete', 'guard_name' => 'web'],
    ]);

    $admin = Role::create([
        'name' => 'admin',
        'guard_name' => 'web',
    ]);

    $manager = Role::create([
        'name' => 'manager',
        'guard_name' => 'web',
    ]);

    $staff = Role::create([
        'name' => 'staff',
        'guard_name' => 'web',
    ]);

    Role::create([
        'name' => 'customer',
        'guard_name' => 'web',
    ]);

    $admin->syncPermissions([
        'rooms.view',
        'rooms.create',
        'rooms.update',
        'rooms.delete',
    ]);

    $manager->syncPermissions([
        'rooms.view',
        'rooms.create',
        'rooms.update',
        'rooms.delete',
    ]);

    $staff->syncPermissions([
        'rooms.view',
    ]);
});

function roomApiUser(string $role): User
{
    $user = User::factory()->create([
        'status' => 'active',
    ]);

    $user->assignRole($role);

    return $user;
}

function roomApiRoomType(?string $name = null): RoomType
{
    return RoomType::create([
        'name' => $name ?? fake()->unique()->words(2, true),
        'capacity' => 2,
        'base_price' => 80,
        'status' => 'active',
    ]);
}

function roomApiRoom(RoomType $roomType, string $number = '101'): Room
{
    return Room::create([
        'room_type_id' => $roomType->id,
        'room_number' => $number,
        'floor' => 1,
        'status' => 'available',
        'description' => 'Test room',
    ]);
}

it('lists rooms publicly', function () {
    $roomType = roomApiRoomType();

    roomApiRoom($roomType, '101');
    roomApiRoom($roomType, '102');

    $response = $this->getJson('/api/v1/rooms');

    $response
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'room_type_id',
                    'room_number',
                    'floor',
                    'status',
                    'description',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);

    expect($response->json('data'))->toHaveCount(2);
});

it('shows one room publicly', function () {
    $roomType = roomApiRoomType('Deluxe Room');
    $room = roomApiRoom($roomType, '101');

    $response = $this->getJson(
        "/api/v1/rooms/{$room->id}"
    );

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $room->id)
        ->assertJsonPath('data.room_number', '101')
        ->assertJsonPath('data.room_type_id', $roomType->id)
        ->assertJsonPath('data.status', 'available');
});

it('allows admin to create a room', function () {
    $admin = roomApiUser('admin');
    $roomType = roomApiRoomType();

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/rooms', [
            'room_type_id' => $roomType->id,
            'room_number' => '201',
            'floor' => 2,
            'status' => 'available',
            'description' => 'New room',
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.room_number', '201')
        ->assertJsonPath('data.floor', 2)
        ->assertJsonPath('data.status', 'available');

    $this->assertDatabaseHas('rooms', [
        'room_type_id' => $roomType->id,
        'room_number' => '201',
    ]);
});

it('allows manager to update a room', function () {
    $manager = roomApiUser('manager');
    $roomType = roomApiRoomType();

    $room = roomApiRoom($roomType, '101');

    $response = $this
        ->actingAs($manager, 'sanctum')
        ->putJson("/api/v1/rooms/{$room->id}", [
            'room_type_id' => $roomType->id,
            'room_number' => '102',
            'floor' => 2,
            'status' => 'maintenance',
            'description' => 'Updated room',
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $room->id)
        ->assertJsonPath('data.room_number', '102')
        ->assertJsonPath('data.status', 'maintenance');

    $this->assertDatabaseHas('rooms', [
        'id' => $room->id,
        'room_number' => '102',
        'status' => 'maintenance',
    ]);
});

it('allows admin to delete a room without booking items', function () {
    $admin = roomApiUser('admin');
    $roomType = roomApiRoomType();
    $room = roomApiRoom($roomType, '101');

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->deleteJson("/api/v1/rooms/{$room->id}");

    $response
        ->assertOk()
        ->assertJsonPath(
            'message',
            'Room deleted successfully.'
        );

    $this->assertDatabaseMissing('rooms', [
        'id' => $room->id,
    ]);
});

it('rejects creating a room with a duplicate room number', function () {
    $admin = roomApiUser('admin');
    $roomType = roomApiRoomType();

    roomApiRoom($roomType, '101');

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/rooms', [
            'room_type_id' => $roomType->id,
            'room_number' => '101',
            'floor' => 1,
            'status' => 'available',
        ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.room_number.0',
            'This room number is already in use.'
        );
});

it('rejects deleting a room referenced by booking items', function () {
    $admin = roomApiUser('admin');
    $roomType = roomApiRoomType();
    $room = roomApiRoom($roomType, '101');

    $guest = Guest::create([
        'full_name' => 'Room API Guest',
        'email' => fake()->unique()->safeEmail(),
        'phone' => '012345678',
        'country' => 'Cambodia',
    ]);

    $booking = Booking::create([
        'booking_code' => 'ROOM-'.fake()->unique()->numerify('#####'),
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

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->deleteJson("/api/v1/rooms/{$room->id}");

    $response
        ->assertStatus(409)
        ->assertJsonPath(
            'message',
            'This room cannot be deleted because it is still referenced by existing booking records.'
        );

    $this->assertDatabaseHas('rooms', [
        'id' => $room->id,
    ]);
});

it('prevents customers from creating rooms', function () {
    $customer = roomApiUser('customer');
    $roomType = roomApiRoomType();

    $response = $this
        ->actingAs($customer, 'sanctum')
        ->postJson('/api/v1/rooms', [
            'room_type_id' => $roomType->id,
            'room_number' => '101',
            'floor' => 1,
            'status' => 'available',
        ]);

    $response->assertForbidden();
});

it('prevents staff from creating rooms', function () {
    $staff = roomApiUser('staff');
    $roomType = roomApiRoomType();

    $response = $this
        ->actingAs($staff, 'sanctum')
        ->postJson('/api/v1/rooms', [
            'room_type_id' => $roomType->id,
            'room_number' => '101',
            'floor' => 1,
            'status' => 'available',
        ]);

    $response->assertForbidden();
});

it('prevents customers from updating rooms', function () {
    $customer = roomApiUser('customer');
    $roomType = roomApiRoomType();
    $room = roomApiRoom($roomType, '101');

    $response = $this
        ->actingAs($customer, 'sanctum')
        ->putJson("/api/v1/rooms/{$room->id}", [
            'room_type_id' => $roomType->id,
            'room_number' => '102',
            'floor' => 1,
            'status' => 'available',
        ]);

    $response->assertForbidden();
});

it('prevents customers from deleting rooms', function () {
    $customer = roomApiUser('customer');
    $roomType = roomApiRoomType();
    $room = roomApiRoom($roomType, '101');

    $response = $this
        ->actingAs($customer, 'sanctum')
        ->deleteJson("/api/v1/rooms/{$room->id}");

    $response->assertForbidden();
});

it('returns 404 for a room that does not exist', function () {
    $response = $this->getJson('/api/v1/rooms/999999');

    $response->assertNotFound();
});

it('paginates rooms', function () {
    $roomType = roomApiRoomType();

    foreach (range(1, 20) as $number) {
        roomApiRoom(
            $roomType,
            str_pad((string) $number, 3, '0', STR_PAD_LEFT)
        );
    }

    $response = $this->getJson(
        '/api/v1/rooms?per_page=10&page=1'
    );

    $response
        ->assertOk()
        ->assertJsonPath('meta.per_page', 10)
        ->assertJsonPath('meta.total', 20);

    expect($response->json('data'))->toHaveCount(10);
});

it('filters rooms by status', function () {
    $roomType = roomApiRoomType();

    roomApiRoom($roomType, '101');

    $room = roomApiRoom($roomType, '102');
    $room->update([
        'status' => 'maintenance',
    ]);

    $response = $this->getJson(
        '/api/v1/rooms?status=maintenance'
    );

    $response->assertOk();

    expect($response->json('data'))->toHaveCount(1);

    expect(
        $response->json('data.0.room_number')
    )->toBe('102');
});

it('searches rooms by room number', function () {
    $roomType = roomApiRoomType();

    roomApiRoom($roomType, 'SEARCH-DEL-101');
    roomApiRoom($roomType, 'SEARCH-STD-201');

    $response = $this->getJson(
        '/api/v1/rooms?search=SEARCH-DEL'
    );

    $response->assertOk();

    expect($response->json('data'))
        ->toHaveCount(1);

    expect(
        $response->json('data.0.room_number')
    )->toBe('SEARCH-DEL-101');
});
