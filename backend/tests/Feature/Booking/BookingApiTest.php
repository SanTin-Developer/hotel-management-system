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
    $permissions = [
        'bookings.view',
        'bookings.create',
        'bookings.update',
        'bookings.cancel',
        'bookings.confirm',
        'bookings.complete',
    ];

    foreach ($permissions as $permission) {
        Permission::firstOrCreate([
            'name' => $permission,
            'guard_name' => 'web',
        ]);
    }

    $manager = Role::firstOrCreate([
        'name' => 'manager',
        'guard_name' => 'web',
    ]);

    $customer = Role::firstOrCreate([
        'name' => 'customer',
        'guard_name' => 'web',
    ]);

    $manager->syncPermissions($permissions);

    $customer->syncPermissions([
        'bookings.create',
        'bookings.cancel',
    ]);
});

function bookingApiUser(string $role = 'manager'): User
{
    $user = User::factory()->create([
        'status' => 'active',
    ]);

    $user->assignRole($role);

    return $user;
}

function bookingApiGuest(): Guest
{
    return Guest::create([
        'full_name' => 'API Test Guest',
        'email' => fake()->unique()->safeEmail(),
        'phone' => '012345678',
        'country' => 'Cambodia',
    ]);
}

function bookingApiRoom(
    string $roomNumber = '101',
    float $price = 80
): Room {
    $roomType = RoomType::create([
        'name' => fake()->unique()->words(2, true),
        'capacity' => 2,
        'base_price' => $price,
        'status' => 'active',
    ]);

    return Room::create([
        'room_type_id' => $roomType->id,
        'room_number' => $roomNumber,
        'floor' => 1,
        'status' => 'available',
        'description' => 'Booking API test room',
    ]);
}

it('requires authentication to create a booking', function () {
    $guest = bookingApiGuest();
    $room = bookingApiRoom();

    $response = $this->postJson('/api/v1/bookings', [
        'guest_id' => $guest->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-13',
        'adults' => 2,
        'children' => 0,
        'room_ids' => [$room->id],
    ]);

    $response->assertUnauthorized();
});

it('creates a booking successfully', function () {
    $user = bookingApiUser();
    $guest = bookingApiGuest();
    $room = bookingApiRoom('101', 80);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/bookings', [
            'guest_id' => $guest->id,
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-13',
            'adults' => 2,
            'children' => 1,
            'room_ids' => [$room->id],
            'special_request' => 'Late check-in',
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.guest_id', $guest->id)
        ->assertJsonPath('data.check_in', '2026-09-10')
        ->assertJsonPath('data.check_out', '2026-09-13')
        ->assertJsonPath('data.adults', 2)
        ->assertJsonPath('data.children', 1)
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonStructure([
            'data' => [
                'id',
                'booking_code',
                'guest_id',
                'check_in',
                'check_out',
                'adults',
                'children',
                'total_amount',
                'booking_source',
                'status',
                'special_request',
                'booking_items',
            ],
        ]);

    expect((float) $response->json('data.total_amount'))
        ->toBe(240.0);

    expect($response->json('data.booking_code'))
        ->toStartWith('BK-');

    $this->assertDatabaseHas('bookings', [
        'guest_id' => $guest->id,
        'status' => 'pending',
        'total_amount' => '240.00',
    ]);

    $this->assertDatabaseHas('booking_items', [
        'room_id' => $room->id,
        'nights' => 3,
        'subtotal' => '240.00',
    ]);
});

it('creates a booking for multiple rooms', function () {
    $user = bookingApiUser();
    $guest = bookingApiGuest();

    $room1 = bookingApiRoom('101', 80);
    $room2 = bookingApiRoom('102', 120);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/bookings', [
            'guest_id' => $guest->id,
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-13',
            'adults' => 2,
            'children' => 0,
            'room_ids' => [
                $room1->id,
                $room2->id,
            ],
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.total_amount', '600.00');

    expect($response->json('data.booking_items'))
        ->toHaveCount(2);

    expect(BookingItem::count())->toBe(2);
});

it('rejects booking when the room is already booked', function () {
    $user = bookingApiUser();
    $guest = bookingApiGuest();

    $room = bookingApiRoom('101', 80);

    $existingBooking = Booking::create([
        'booking_code' => 'EXISTING-API-001',
        'guest_id' => $guest->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-13',
        'adults' => 2,
        'children' => 0,
        'total_amount' => 240,
        'booking_source' => 'website',
        'status' => 'confirmed',
    ]);

    BookingItem::create([
        'booking_id' => $existingBooking->id,
        'room_id' => $room->id,
        'price_per_night' => 80,
        'nights' => 3,
        'subtotal' => 240,
        'status' => 'reserved',
    ]);

    $newGuest = bookingApiGuest();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/bookings', [
            'guest_id' => $newGuest->id,
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-13',
            'adults' => 2,
            'children' => 0,
            'room_ids' => [$room->id],
        ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.room_ids.0',
            'One or more selected rooms are no longer available for the requested dates.'
        );

    expect(Booking::count())->toBe(1);
    expect(BookingItem::count())->toBe(1);
});

it('rejects booking for a room in maintenance', function () {
    $user = bookingApiUser();
    $guest = bookingApiGuest();

    $room = bookingApiRoom();

    $room->update([
        'status' => 'maintenance',
    ]);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/bookings', [
            'guest_id' => $guest->id,
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-13',
            'adults' => 2,
            'children' => 0,
            'room_ids' => [$room->id],
        ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.room_ids.0',
            'One or more selected rooms are not currently available.'
        );
});

it('rejects an invalid guest', function () {
    $user = bookingApiUser();
    $room = bookingApiRoom();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/bookings', [
            'guest_id' => 999999,
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-13',
            'adults' => 2,
            'children' => 0,
            'room_ids' => [$room->id],
        ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.guest_id.0',
            'The selected guest does not exist.'
        );
});

it('rejects duplicate room ids', function () {
    $user = bookingApiUser();
    $guest = bookingApiGuest();
    $room = bookingApiRoom();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/bookings', [
            'guest_id' => $guest->id,
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-13',
            'adults' => 2,
            'children' => 0,
            'room_ids' => [
                $room->id,
                $room->id,
            ],
        ]);

    $response->assertStatus(422);

    expect(
        $response->json('errors')['room_ids.1'][0]
    )->toBe('Duplicate room IDs are not allowed.');
});

it('does not trust a client supplied total amount', function () {
    $user = bookingApiUser();
    $guest = bookingApiGuest();
    $room = bookingApiRoom('101', 80);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/bookings', [
            'guest_id' => $guest->id,
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-13',
            'adults' => 2,
            'children' => 0,
            'room_ids' => [$room->id],
            'total_amount' => 1,
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.total_amount', '240.00');
});

it('returns 404 when a selected room does not exist', function () {
    $user = bookingApiUser();
    $guest = bookingApiGuest();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/bookings', [
            'guest_id' => $guest->id,
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-13',
            'adults' => 2,
            'children' => 0,
            'room_ids' => [999999],
        ]);

    $response->assertStatus(422);

    expect(
        $response->json('errors')['room_ids.0'][0]
    )->toBe('One or more selected rooms do not exist.');
});

it('paginates bookings', function () {
    $user = bookingApiUser();
    $guest = bookingApiGuest();

    for ($i = 1; $i <= 25; $i++) {
        Booking::create([
            'booking_code' => "PAGE-{$i}",
            'guest_id' => $guest->id,
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-13',
            'adults' => 2,
            'children' => 0,
            'total_amount' => 240,
            'booking_source' => 'website',
            'status' => 'confirmed',
        ]);
    }

    $response = $this
        ->actingAs($user, 'sanctum')
        ->getJson('/api/v1/bookings?per_page=10&page=1');

    $response
        ->assertOk()
        ->assertJsonPath('meta.per_page', 10)
        ->assertJsonPath('meta.total', 25);

    expect($response->json('data'))->toHaveCount(10);
});

it('filters bookings by status', function () {
    $user = bookingApiUser();
    $guest = bookingApiGuest();

    Booking::create([
        'booking_code' => 'CONFIRMED-001',
        'guest_id' => $guest->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-13',
        'adults' => 2,
        'children' => 0,
        'total_amount' => 240,
        'booking_source' => 'website',
        'status' => 'confirmed',
    ]);

    Booking::create([
        'booking_code' => 'CANCELLED-001',
        'guest_id' => $guest->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-13',
        'adults' => 2,
        'children' => 0,
        'total_amount' => 240,
        'booking_source' => 'website',
        'status' => 'cancelled',
    ]);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->getJson('/api/v1/bookings?status=confirmed');

    $response->assertOk();

    expect($response->json('data'))->toHaveCount(1);

    expect(
        $response->json('data.0.status')
    )->toBe('confirmed');
});

it('searches bookings by booking code', function () {
    $user = bookingApiUser();
    $guest = bookingApiGuest();

    Booking::create([
        'booking_code' => 'SEARCH-ABC-001',
        'guest_id' => $guest->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-13',
        'adults' => 2,
        'children' => 0,
        'total_amount' => 240,
        'booking_source' => 'website',
        'status' => 'confirmed',
    ]);

    Booking::create([
        'booking_code' => 'OTHER-XYZ-001',
        'guest_id' => $guest->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-13',
        'adults' => 2,
        'children' => 0,
        'total_amount' => 240,
        'booking_source' => 'website',
        'status' => 'confirmed',
    ]);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->getJson('/api/v1/bookings?search=SEARCH-ABC');

    $response->assertOk();

    expect($response->json('data'))->toHaveCount(1);

    expect(
        $response->json('data.0.booking_code')
    )->toBe('SEARCH-ABC-001');
});

it('prevents a customer from booking for another guest', function () {
    $customer = bookingApiUser('customer');

    $otherGuest = bookingApiGuest();
    $room = bookingApiRoom('OWN-101', 80);

    $response = $this
        ->actingAs($customer, 'sanctum')
        ->postJson('/api/v1/bookings', [
            'guest_id' => $otherGuest->id,
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-13',
            'adults' => 2,
            'children' => 0,
            'room_ids' => [$room->id],
        ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.guest_id.0',
            'You can only create a booking for your own guest profile.'
        );
});

it('allows a customer to book for their own guest profile', function () {
    $customer = bookingApiUser();

    $customer->update([
        'email' => 'owner@example.com',
    ]);

    $guest = Guest::create([
        'full_name' => 'Owner Guest',
        'email' => 'owner@example.com',
        'phone' => '012345678',
        'country' => 'Cambodia',
    ]);

    $room = bookingApiRoom('OWN-102', 80);

    $response = $this
        ->actingAs($customer, 'sanctum')
        ->postJson('/api/v1/bookings', [
            'guest_id' => $guest->id,
            'check_in' => '2026-09-10',
            'check_out' => '2026-09-13',
            'adults' => 2,
            'children' => 0,
            'room_ids' => [$room->id],
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.guest_id', $guest->id);
});
