<?php

use App\Models\Booking;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::insert([
        [
            'name' => 'bookings.create',
            'guard_name' => 'web',
        ],
        [
            'name' => 'bookings.cancel',
            'guard_name' => 'web',
        ],
        [
            'name' => 'bookings.confirm',
            'guard_name' => 'web',
        ],
        [
            'name' => 'bookings.complete',
            'guard_name' => 'web',
        ],
    ]);

    $manager = Role::create([
        'name' => 'manager',
        'guard_name' => 'web',
    ]);

    $customer = Role::create([
        'name' => 'customer',
        'guard_name' => 'web',
    ]);

    $manager->syncPermissions([
        'bookings.create',
        'bookings.cancel',
        'bookings.confirm',
        'bookings.complete',
    ]);

    $customer->syncPermissions([
        'bookings.create',
        'bookings.cancel',
    ]);
});

function statusApiUser(string $role = 'manager'): User
{
    $user = User::factory()->create([
        'status' => 'active',
    ]);

    $user->assignRole($role);

    return $user;
}

function statusApiGuest(): Guest
{
    return Guest::create([
        'full_name' => 'Status API Guest',
        'email' => fake()->unique()->safeEmail(),
        'phone' => '012345678',
        'country' => 'Cambodia',
    ]);
}

function statusApiBooking(string $status = 'pending'): Booking
{
    return Booking::create([
        'booking_code' => 'API-'.fake()->unique()->numerify('#####'),
        'guest_id' => statusApiGuest()->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-13',
        'adults' => 2,
        'children' => 0,
        'total_amount' => 240,
        'booking_source' => 'website',
        'status' => $status,
    ]);
}

it('requires authentication to confirm a booking', function () {
    $booking = statusApiBooking();

    $response = $this->postJson(
        "/api/v1/bookings/{$booking->id}/confirm"
    );

    $response->assertUnauthorized();
});

it('confirms a pending booking', function () {
    $user = statusApiUser();
    $booking = statusApiBooking('pending');

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson("/api/v1/bookings/{$booking->id}/confirm");

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $booking->id)
        ->assertJsonPath('data.status', 'confirmed');

    $this->assertDatabaseHas('booking_status_histories', [
        'booking_id' => $booking->id,
        'status' => 'confirmed',
        'changed_by' => $user->id,
    ]);
});

it('cancels a pending booking', function () {
    $user = statusApiUser();
    $booking = statusApiBooking('pending');

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson("/api/v1/bookings/{$booking->id}/cancel");

    $response
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');

    $this->assertDatabaseHas('booking_status_histories', [
        'booking_id' => $booking->id,
        'status' => 'cancelled',
        'changed_by' => $user->id,
    ]);
});

it('completes a confirmed booking', function () {
    $user = statusApiUser();
    $booking = statusApiBooking('confirmed');

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson("/api/v1/bookings/{$booking->id}/complete");

    $response
        ->assertOk()
        ->assertJsonPath('data.status', 'completed');

    $this->assertDatabaseHas('booking_status_histories', [
        'booking_id' => $booking->id,
        'status' => 'completed',
        'changed_by' => $user->id,
    ]);
});

it('cancels a confirmed booking', function () {
    $user = statusApiUser();
    $booking = statusApiBooking('confirmed');

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson("/api/v1/bookings/{$booking->id}/cancel");

    $response
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');
});

it('rejects confirming a cancelled booking', function () {
    $user = statusApiUser();
    $booking = statusApiBooking('cancelled');

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson("/api/v1/bookings/{$booking->id}/confirm");

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.status.0',
            'Cannot change booking status from cancelled to confirmed.'
        );
});

it('rejects cancelling a completed booking', function () {
    $user = statusApiUser();
    $booking = statusApiBooking('completed');

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson("/api/v1/bookings/{$booking->id}/cancel");

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.status.0',
            'Cannot change booking status from completed to cancelled.'
        );
});

it('rejects completing a pending booking', function () {
    $user = statusApiUser();
    $booking = statusApiBooking('pending');

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson("/api/v1/bookings/{$booking->id}/complete");

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.status.0',
            'Cannot change booking status from pending to completed.'
        );
});

it('returns 404 for a missing booking', function () {
    $user = statusApiUser();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/bookings/999999/confirm');

    $response->assertNotFound();
});

it('prevents a customer from cancelling another customers booking', function () {
    $customer = User::factory()->create([
        'status' => 'active',
    ]);

    $booking = statusApiBooking('confirmed');

    $response = $this
        ->actingAs($customer, 'sanctum')
        ->postJson("/api/v1/bookings/{$booking->id}/cancel");

    $response->assertForbidden();
});

it('allows a customer to cancel their own booking', function () {
    $customer = User::factory()->create([
        'status' => 'active',
        'email' => 'customer@example.com',
    ]);

    $customer->assignRole('customer');

    $guest = Guest::create([
        'full_name' => 'Customer Guest',
        'email' => 'customer@example.com',
        'phone' => '012345678',
        'country' => 'Cambodia',
    ]);

    $booking = Booking::create([
        'booking_code' => 'OWN-001',
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
        ->actingAs($customer, 'sanctum')
        ->postJson("/api/v1/bookings/{$booking->id}/cancel");

    $response
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');
});
