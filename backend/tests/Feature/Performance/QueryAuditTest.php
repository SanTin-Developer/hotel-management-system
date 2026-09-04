<?php

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['cache.stores.redis.driver' => 'array']);

    $permission = Permission::create([
        'name' => 'dashboard.view',
        'guard_name' => 'web',
    ]);

    $role = Role::create([
        'name' => 'manager',
        'guard_name' => 'web',
    ]);

    $role->givePermissionTo($permission);
});

function performanceUser(): User
{
    $user = User::factory()->create([
        'status' => 'active',
    ]);

    $user->assignRole('manager');

    return $user;
}

function performanceGuest(): Guest
{
    return Guest::create([
        'full_name' => fake()->unique()->name(),
        'email' => fake()->unique()->safeEmail(),
        'phone' => '012345678',
        'country' => 'Cambodia',
    ]);
}

function performanceRoomType(): RoomType
{
    return RoomType::create([
        'name' => fake()->unique()->words(2, true),
        'capacity' => 2,
        'base_price' => 100,
        'status' => 'active',
    ]);
}

function performanceRoom(
    RoomType $roomType,
    string $number
): Room {
    return Room::create([
        'room_type_id' => $roomType->id,
        'room_number' => $number,
        'floor' => 1,
        'status' => 'available',
    ]);
}

it('does not create an excessive number of queries for the booking list', function () {
    $user = performanceUser();
    $guest = performanceGuest();
    $roomType = performanceRoomType();

    for ($i = 1; $i <= 10; $i++) {
        $room = performanceRoom(
            $roomType,
            'Q'.str_pad((string) $i, 3, '0', STR_PAD_LEFT)
        );

        Booking::create([
            'booking_code' => 'QUERY-'.$i,
            'guest_id' => $guest->id,
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-13',
            'adults' => 2,
            'children' => 0,
            'total_amount' => 300,
            'booking_source' => 'website',
            'status' => 'confirmed',
        ]);
    }

    DB::enableQueryLog();

    $this
        ->actingAs($user, 'sanctum')
        ->getJson('/api/v1/bookings?per_page=10')
        ->assertOk();

    $queries = DB::getQueryLog();

    dump([
        'query_count' => count($queries),
        'queries' => collect($queries)->pluck('query')->all(),
    ]);

    expect(count($queries))->toBeLessThan(20);
});

it('does not create an excessive number of queries for the dashboard', function () {
    $user = performanceUser();

    $roomType = performanceRoomType();

    for ($i = 1; $i <= 10; $i++) {
        performanceRoom(
            $roomType,
            'D'.str_pad((string) $i, 3, '0', STR_PAD_LEFT)
        );
    }

    $guest = performanceGuest();

    for ($i = 1; $i <= 10; $i++) {
        Booking::create([
            'booking_code' => 'DASH-QUERY-'.$i,
            'guest_id' => $guest->id,
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-13',
            'adults' => 2,
            'children' => 0,
            'total_amount' => 300,
            'booking_source' => 'website',
            'status' => 'confirmed',
        ]);
    }

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this
        ->actingAs($user, 'sanctum')
        ->getJson('/api/v1/dashboard/summary')
        ->assertOk();

    $queries = DB::getQueryLog();

    dump([
        'query_count' => count($queries),
        'queries' => collect($queries)->pluck('query')->all(),
    ]);

    expect(count($queries))->toBeLessThan(25);
});
