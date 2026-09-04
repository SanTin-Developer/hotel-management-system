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
        ['name' => 'guests.view', 'guard_name' => 'web'],
        ['name' => 'guests.create', 'guard_name' => 'web'],
        ['name' => 'guests.update', 'guard_name' => 'web'],
        ['name' => 'guests.delete', 'guard_name' => 'web'],
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
        'guests.view',
        'guests.create',
        'guests.update',
        'guests.delete',
    ]);

    $manager->syncPermissions([
        'guests.view',
        'guests.create',
        'guests.update',
        'guests.delete',
    ]);

    $staff->syncPermissions([
        'guests.view',
    ]);
});

function guestApiUser(string $role): User
{
    $user = User::factory()->create([
        'status' => 'active',
    ]);

    $user->assignRole($role);

    return $user;
}

function guestApiGuest(): Guest
{
    return Guest::factory()->create();
}

it('requires authentication to list guests', function () {
    $this->getJson('/api/v1/guests')->assertUnauthorized();
});

it('lists guests', function () {
    $user = guestApiUser('admin');

    guestApiGuest();
    guestApiGuest();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->getJson('/api/v1/guests');

    $response
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('filters guests by search query', function () {
    $user = guestApiUser('admin');

    Guest::factory()->create([
        'full_name' => 'Alice Johnson',
        'email' => 'alice@example.com',
    ]);

    Guest::factory()->create([
        'full_name' => 'Bob Smith',
        'email' => 'bob@example.com',
    ]);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->getJson('/api/v1/guests?search=Alice');

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.full_name', 'Alice Johnson');
});

it('filters guests by country', function () {
    $user = guestApiUser('admin');

    Guest::factory()->create([
        'full_name' => 'Cambodian Guest',
        'country' => 'Cambodia',
    ]);

    Guest::factory()->create([
        'full_name' => 'American Guest',
        'country' => 'United States',
    ]);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->getJson('/api/v1/guests?country=Cambodia');

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.country', 'Cambodia');
});

it('denies staff from listing guests without permission', function () {
    $customer = guestApiUser('customer');

    $this
        ->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/guests')
        ->assertForbidden();
});

it('shows a single guest with booking and review counts', function () {
    $user = guestApiUser('admin');

    $guest = guestApiGuest();

    Booking::create([
        'booking_code' => 'GST-'.fake()->unique()->numerify('#####'),
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
        ->getJson("/api/v1/guests/{$guest->id}");

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $guest->id)
        ->assertJsonPath('data.full_name', $guest->full_name)
        ->assertJsonPath('data.bookings_count', 1);
});

it('requires authentication to create a guest', function () {
    $this->postJson('/api/v1/guests', [
        'full_name' => 'Test Guest',
    ])->assertUnauthorized();
});

it('creates a guest', function () {
    $user = guestApiUser('manager');

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/guests', [
            'full_name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '012345678',
            'country' => 'Cambodia',
            'gender' => 'male',
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.full_name', 'John Doe')
        ->assertJsonPath('data.email', 'john.doe@example.com')
        ->assertJsonPath('data.country', 'Cambodia');

    $this->assertDatabaseHas('guests', [
        'full_name' => 'John Doe',
        'email' => 'john.doe@example.com',
    ]);
});

it('validates guest name is required', function () {
    $user = guestApiUser('manager');

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/guests', []);

    $response
        ->assertStatus(422)
        ->assertJsonPath('errors.full_name.0', 'Guest full name is required.');
});

it('validates guest email format', function () {
    $user = guestApiUser('manager');

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/guests', [
            'full_name' => 'John Doe',
            'email' => 'invalid-email',
        ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath('errors.email.0', 'Please provide a valid email address.');
});

it('validates gender enum', function () {
    $user = guestApiUser('manager');

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/guests', [
            'full_name' => 'John Doe',
            'gender' => 'unknown',
        ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.gender.0',
            'Invalid gender. Must be male, female, or other.'
        );
});

it('validates date of birth is in the past', function () {
    $user = guestApiUser('manager');

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/guests', [
            'full_name' => 'John Doe',
            'date_of_birth' => now()->addDay()->toDateString(),
        ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.date_of_birth.0',
            'Date of birth must be in the past.'
        );
});

it('updates a guest', function () {
    $user = guestApiUser('admin');

    $guest = guestApiGuest();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->putJson("/api/v1/guests/{$guest->id}", [
            'full_name' => 'Updated Name',
            'phone' => '099999999',
        ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.full_name', 'Updated Name')
        ->assertJsonPath('data.phone', '099999999');

    $this->assertDatabaseHas('guests', [
        'id' => $guest->id,
        'full_name' => 'Updated Name',
    ]);
});

it('deletes a guest', function () {
    $user = guestApiUser('admin');

    $guest = guestApiGuest();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/guests/{$guest->id}");

    $response
        ->assertOk()
        ->assertJsonPath('message', 'Guest deleted successfully.');

    $this->assertDatabaseMissing('guests', [
        'id' => $guest->id,
    ]);
});

it('cannot delete a guest with bookings', function () {
    $user = guestApiUser('admin');

    $guest = guestApiGuest();

    Booking::create([
        'booking_code' => 'GST-DEL-'.fake()->unique()->numerify('#####'),
        'guest_id' => $guest->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-13',
        'adults' => 1,
        'children' => 0,
        'total_amount' => 120,
        'booking_source' => 'website',
        'status' => 'confirmed',
    ]);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/guests/{$guest->id}");

    $response
        ->assertStatus(409)
        ->assertJsonPath(
            'message',
            'This guest cannot be deleted because they have existing booking records.'
        );

    $this->assertDatabaseHas('guests', [
        'id' => $guest->id,
    ]);
});

it('denies guest creation without permission', function () {
    $customer = guestApiUser('customer');

    $this
        ->actingAs($customer, 'sanctum')
        ->postJson('/api/v1/guests', [
            'full_name' => 'Should Fail',
        ])
        ->assertForbidden();
});
