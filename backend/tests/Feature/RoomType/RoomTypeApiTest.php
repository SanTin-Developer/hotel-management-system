<?php

use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::insert([
        [
            'name' => 'room-types.view',
            'guard_name' => 'web',
        ],
        [
            'name' => 'room-types.create',
            'guard_name' => 'web',
        ],
        [
            'name' => 'room-types.update',
            'guard_name' => 'web',
        ],
        [
            'name' => 'room-types.delete',
            'guard_name' => 'web',
        ],
    ]);

    $adminRole = Role::create([
        'name' => 'admin',
        'guard_name' => 'web',
    ]);

    $managerRole = Role::create([
        'name' => 'manager',
        'guard_name' => 'web',
    ]);

    $staffRole = Role::create([
        'name' => 'staff',
        'guard_name' => 'web',
    ]);

    Role::create([
        'name' => 'customer',
        'guard_name' => 'web',
    ]);

    $adminRole->syncPermissions([
        'room-types.view',
        'room-types.create',
        'room-types.update',
        'room-types.delete',
    ]);

    $managerRole->syncPermissions([
        'room-types.view',
        'room-types.create',
        'room-types.update',
        'room-types.delete',
    ]);

    $staffRole->syncPermissions([
        'room-types.view',
    ]);
});

it('lists room types', function () {
    RoomType::create([
        'name' => 'Deluxe Room',
        'capacity' => 2,
        'base_price' => 80,
        'status' => 'active',
    ]);

    RoomType::create([
        'name' => 'Suite',
        'capacity' => 4,
        'base_price' => 150,
        'status' => 'active',
    ]);

    $response = $this->getJson('/api/v1/room-types');

    $response
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'description',
                    'capacity',
                    'base_price',
                    'size',
                    'bed_type',
                    'image_url',
                    'status',
                    'rooms_count',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);

    expect($response->json('data'))->toHaveCount(2);
});

it('shows one room type', function () {
    $roomType = RoomType::create([
        'name' => 'Deluxe Room',
        'description' => 'A spacious room',
        'capacity' => 2,
        'base_price' => 80,
        'size' => 35,
        'bed_type' => 'King Bed',
        'status' => 'active',
    ]);

    $response = $this->getJson(
        "/api/v1/room-types/{$roomType->id}"
    );

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $roomType->id)
        ->assertJsonPath('data.name', 'Deluxe Room')
        ->assertJsonPath('data.capacity', 2)
        ->assertJsonPath('data.status', 'active');
});

it('creates a room type', function () {
    $admin = roomTypeUser('admin');

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/room-types', [
            'name' => 'Premium Room',
            'description' => 'Premium room',
            'capacity' => 3,
            'base_price' => 120,
            'size' => 40,
            'bed_type' => 'King Bed',
            'image_url' => 'https://example.com/premium.jpg',
            'status' => 'active',
        ]);

    $response
        ->assertStatus(201)
        ->assertJsonPath('data.name', 'Premium Room')
        ->assertJsonPath('data.capacity', 3)
        ->assertJsonPath('data.base_price', '120.00')
        ->assertJsonPath('data.status', 'active');

    $this->assertDatabaseHas('room_types', [
        'name' => 'Premium Room',
        'capacity' => 3,
        'status' => 'active',
    ]);
});

it('rejects a duplicate room type name', function () {
    $admin = roomTypeUser('admin');

    RoomType::create([
        'name' => 'Deluxe Room',
        'capacity' => 2,
        'base_price' => 80,
        'status' => 'active',
    ]);

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/room-types', [
            'name' => 'Deluxe Room',
            'capacity' => 3,
            'base_price' => 100,
            'status' => 'active',
        ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.name.0',
            'A room type with this name already exists.'
        );
});

it('updates a room type', function () {
    $manager = roomTypeUser('manager');

    $roomType = RoomType::create([
        'name' => 'Standard Room',
        'capacity' => 2,
        'base_price' => 50,
        'status' => 'active',
    ]);

    $response = $this
        ->actingAs($manager, 'sanctum')
        ->putJson(
            "/api/v1/room-types/{$roomType->id}",
            [
                'name' => 'Updated Standard Room',
                'description' => 'Updated description',
                'capacity' => 3,
                'base_price' => 65,
                'size' => 40,
                'bed_type' => 'King Bed',
                'image_url' => 'https://example.com/updated.jpg',
                'status' => 'active',
            ]
        );

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $roomType->id)
        ->assertJsonPath('data.name', 'Updated Standard Room')
        ->assertJsonPath('data.capacity', 3)
        ->assertJsonPath('data.base_price', '65.00');

    $this->assertDatabaseHas('room_types', [
        'id' => $roomType->id,
        'name' => 'Updated Standard Room',
        'capacity' => 3,
        'status' => 'active',
    ]);
});

it('deletes a room type without rooms', function () {
    $admin = roomTypeUser('admin');

    $roomType = RoomType::create([
        'name' => 'Temporary Room',
        'capacity' => 2,
        'base_price' => 40,
        'status' => 'active',
    ]);

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->deleteJson(
            "/api/v1/room-types/{$roomType->id}"
        );

    $response
        ->assertOk()
        ->assertJsonPath(
            'message',
            'Room type deleted successfully.'
        );

    $this->assertDatabaseMissing('room_types', [
        'id' => $roomType->id,
    ]);
});

it('rejects deleting a room type that still has rooms', function () {
    $admin = roomTypeUser('admin');

    $roomType = RoomType::create([
        'name' => 'Deluxe Room',
        'capacity' => 2,
        'base_price' => 80,
        'status' => 'active',
    ]);

    Room::create([
        'room_type_id' => $roomType->id,
        'room_number' => '101',
        'floor' => 1,
        'status' => 'available',
        'description' => 'Test room',
    ]);

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->deleteJson(
            "/api/v1/room-types/{$roomType->id}"
        );

    $response
        ->assertStatus(409)
        ->assertJsonPath(
            'message',
            'This room type cannot be deleted because it is still assigned to one or more rooms.'
        );

    $this->assertDatabaseHas('room_types', [
        'id' => $roomType->id,
    ]);
});

it('returns 404 for a room type that does not exist', function () {
    $response = $this->getJson('/api/v1/room-types/999999');

    $response->assertNotFound();
});

function roomTypeUser(string $role): User
{
    $user = User::factory()->create([
        'status' => 'active',
    ]);

    $user->assignRole($role);

    return $user;
}

it('prevents customers from creating room types', function () {
    $customer = roomTypeUser('customer');

    $response = $this
        ->actingAs($customer, 'sanctum')
        ->postJson('/api/v1/room-types', [
            'name' => 'Customer Room',
            'capacity' => 2,
            'base_price' => 80,
            'status' => 'active',
        ]);

    $response->assertForbidden();
});

it('prevents staff from creating room types', function () {
    $staff = roomTypeUser('staff');

    $response = $this
        ->actingAs($staff, 'sanctum')
        ->postJson('/api/v1/room-types', [
            'name' => 'Staff Room',
            'capacity' => 2,
            'base_price' => 80,
            'status' => 'active',
        ]);

    $response->assertForbidden();
});

it('allows admin to create room types', function () {
    $admin = roomTypeUser('admin');

    $response = $this
        ->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/room-types', [
            'name' => 'Admin Room',
            'capacity' => 2,
            'base_price' => 80,
            'status' => 'active',
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.name', 'Admin Room');
});

it('allows manager to update room types', function () {
    $manager = roomTypeUser('manager');

    $roomType = RoomType::create([
        'name' => 'Standard Room',
        'capacity' => 2,
        'base_price' => 50,
        'status' => 'active',
    ]);

    $response = $this
        ->actingAs($manager, 'sanctum')
        ->putJson("/api/v1/room-types/{$roomType->id}", [
            'name' => 'Updated Standard Room',
            'capacity' => 3,
            'base_price' => 65,
            'status' => 'active',
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Standard Room');
});

it('prevents customers from deleting room types', function () {
    $customer = roomTypeUser('customer');

    $roomType = RoomType::create([
        'name' => 'Delete Test Room',
        'capacity' => 2,
        'base_price' => 50,
        'status' => 'active',
    ]);

    $response = $this
        ->actingAs($customer, 'sanctum')
        ->deleteJson("/api/v1/room-types/{$roomType->id}");

    $response->assertForbidden();
});

it('paginates room types', function () {
    foreach (range(1, 20) as $number) {
        RoomType::create([
            'name' => "Room Type {$number}",
            'capacity' => 2,
            'base_price' => 80,
            'status' => 'active',
        ]);
    }

    $response = $this->getJson(
        '/api/v1/room-types?per_page=10&page=1'
    );

    $response
        ->assertOk()
        ->assertJsonPath('meta.per_page', 10)
        ->assertJsonPath('meta.total', 20);

    expect($response->json('data'))
        ->toHaveCount(10);
});

it('filters room types by status', function () {
    RoomType::create([
        'name' => 'Active Deluxe',
        'capacity' => 2,
        'base_price' => 100,
        'status' => 'active',
    ]);

    RoomType::create([
        'name' => 'Inactive Deluxe',
        'capacity' => 2,
        'base_price' => 100,
        'status' => 'inactive',
    ]);

    $response = $this->getJson(
        '/api/v1/room-types?status=inactive'
    );

    $response->assertOk();

    expect($response->json('data'))
        ->toHaveCount(1);
});

it('searches room types by name', function () {
    RoomType::create([
        'name' => 'Search Deluxe Room',
        'capacity' => 2,
        'base_price' => 100,
        'status' => 'active',
    ]);

    RoomType::create([
        'name' => 'Search Standard Room',
        'capacity' => 2,
        'base_price' => 80,
        'status' => 'active',
    ]);

    $response = $this->getJson(
        '/api/v1/room-types?search=Deluxe'
    );

    $response->assertOk();

    expect($response->json('data'))
        ->toHaveCount(1);

    expect(
        $response->json('data.0.name')
    )->toBe('Search Deluxe Room');
});
