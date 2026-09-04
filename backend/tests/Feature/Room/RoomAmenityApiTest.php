<?php

use App\Models\Amenity;
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
            'name' => 'rooms.view',
            'guard_name' => 'web',
        ],
        [
            'name' => 'rooms.create',
            'guard_name' => 'web',
        ],
        [
            'name' => 'rooms.update',
            'guard_name' => 'web',
        ],
        [
            'name' => 'rooms.delete',
            'guard_name' => 'web',
        ],
    ]);

    $admin = Role::create([
        'name' => 'admin',
        'guard_name' => 'web',
    ]);

    $manager = Role::create([
        'name' => 'manager',
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
});

function roomAmenityUser(string $role): User
{
    $user = User::factory()->create([
        'status' => 'active',
    ]);

    $user->assignRole($role);

    return $user;
}

function roomAmenityRoom(): Room
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
        'description' => 'Test room',
    ]);
}

function createAmenity(string $name): Amenity
{
    return Amenity::create([
        'name' => $name,
        'description' => "{$name} amenity",
        'icon' => strtolower(str_replace(' ', '-', $name)),
    ]);
}

it('syncs amenities to a room', function () {
    $manager = roomAmenityUser('manager');
    $room = roomAmenityRoom();

    $wifi = createAmenity('Wi-Fi');
    $tv = createAmenity('TV');
    $pool = createAmenity('Swimming Pool');

    $response = $this
        ->actingAs($manager, 'sanctum')
        ->putJson("/api/v1/rooms/{$room->id}/amenities", [
            'amenity_ids' => [
                $wifi->id,
                $tv->id,
                $pool->id,
            ],
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $room->id)
        ->assertJsonCount(3, 'data.amenities');

    $room->refresh();

    expect($room->amenities()->pluck('amenities.id')->sort()->values()->all())
        ->toBe(
            collect([
                $wifi->id,
                $tv->id,
                $pool->id,
            ])->sort()->values()->all()
        );
});

it('replaces the existing room amenities when syncing', function () {
    $manager = roomAmenityUser('manager');
    $room = roomAmenityRoom();

    $wifi = createAmenity('Wi-Fi');
    $tv = createAmenity('TV');
    $pool = createAmenity('Swimming Pool');

    $room->amenities()->attach([
        $wifi->id,
        $tv->id,
    ]);

    $response = $this
        ->actingAs($manager, 'sanctum')
        ->putJson("/api/v1/rooms/{$room->id}/amenities", [
            'amenity_ids' => [
                $pool->id,
            ],
        ]);

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data.amenities');

    $room->refresh();

    expect($room->amenities()->pluck('amenities.id')->all())
        ->toBe([$pool->id]);
});

it('removes one amenity from a room', function () {
    $manager = roomAmenityUser('manager');
    $room = roomAmenityRoom();

    $wifi = createAmenity('Wi-Fi');
    $tv = createAmenity('TV');

    $room->amenities()->attach([
        $wifi->id,
        $tv->id,
    ]);

    $response = $this
        ->actingAs($manager, 'sanctum')
        ->deleteJson(
            "/api/v1/rooms/{$room->id}/amenities/{$wifi->id}"
        );

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $room->id)
        ->assertJsonCount(1, 'data.amenities');

    $room->refresh();

    expect($room->amenities()->pluck('amenities.id')->all())
        ->toBe([$tv->id]);
});

it('rejects a non existing amenity id', function () {
    $manager = roomAmenityUser('manager');
    $room = roomAmenityRoom();

    $response = $this
        ->actingAs($manager, 'sanctum')
        ->putJson("/api/v1/rooms/{$room->id}/amenities", [
            'amenity_ids' => [
                999999,
            ],
        ]);

    $response->assertStatus(422);

    expect(
        $response->json('errors')['amenity_ids.0'][0]
    )->toBe('One or more selected amenities do not exist.');
});

// it('rejects a non existing amenity id', function () {
//     $manager = roomAmenityUser('manager');
//     $room = roomAmenityRoom();

//     $response = $this
//         ->actingAs($manager, 'sanctum')
//         ->putJson("/api/v1/rooms/{$room->id}/amenities", [
//             'amenity_ids' => [
//                 999999,
//             ],
//         ]);

//     $response->assertStatus(422);

//     expect(
//         $response->json('errors')['amenity_ids.0'][0]
//     )->toBe('One or more selected amenities do not exist.');
// });

// it('requires amenity ids', function () {
//     $manager = roomAmenityUser('manager');
//     $room = roomAmenityRoom();

//     $response = $this
//         ->actingAs($manager, 'sanctum')
//         ->putJson("/api/v1/rooms/{$room->id}/amenities", []);

//     $response
//         ->assertStatus(422)
//         ->assertJsonPath(
//             'errors.amenity_ids.0',
//             'Amenity IDs are required.'
//         );
// });

it('prevents customers from syncing room amenities', function () {
    $customer = User::factory()->create([
        'status' => 'active',
    ]);

    Role::firstOrCreate([
        'name' => 'customer',
        'guard_name' => 'web',
    ]);

    $customer->assignRole('customer');

    $room = roomAmenityRoom();
    $wifi = createAmenity('Wi-Fi');

    $response = $this
        ->actingAs($customer, 'sanctum')
        ->putJson("/api/v1/rooms/{$room->id}/amenities", [
            'amenity_ids' => [
                $wifi->id,
            ],
        ]);

    $response->assertForbidden();
});

it('prevents customers from removing room amenities', function () {
    $customer = User::factory()->create([
        'status' => 'active',
    ]);

    Role::firstOrCreate([
        'name' => 'customer',
        'guard_name' => 'web',
    ]);

    $customer->assignRole('customer');

    $room = roomAmenityRoom();
    $wifi = createAmenity('Wi-Fi');

    $room->amenities()->attach($wifi->id);

    $response = $this
        ->actingAs($customer, 'sanctum')
        ->deleteJson(
            "/api/v1/rooms/{$room->id}/amenities/{$wifi->id}"
        );

    $response->assertForbidden();
});

it('returns 404 when the room does not exist', function () {
    $manager = roomAmenityUser('manager');
    $wifi = createAmenity('Wi-Fi');

    $response = $this
        ->actingAs($manager, 'sanctum')
        ->putJson('/api/v1/rooms/999999/amenities', [
            'amenity_ids' => [
                $wifi->id,
            ],
        ]);

    $response->assertNotFound();
});
