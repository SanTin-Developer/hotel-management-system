<?php

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $permissions = [
        'payments.create',
        'payments.update',
        'payments.refund',
    ];

    foreach ($permissions as $permission) {
        Permission::create([
            'name' => $permission,
            'guard_name' => 'web',
        ]);
    }

    $manager = Role::create([
        'name' => 'manager',
        'guard_name' => 'web',
    ]);

    $manager->syncPermissions($permissions);
});

afterEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function paymentApiUser(string $role = 'manager'): User
{
    $user = User::factory()->create([
        'status' => 'active',
    ]);

    $user->assignRole($role);

    return $user;
}

function paymentApiGuest(): Guest
{
    return Guest::create([
        'full_name' => 'Payment API Guest',
        'email' => fake()->unique()->safeEmail(),
        'phone' => '012345678',
        'country' => 'Cambodia',
    ]);
}

function paymentApiBooking(
    string $status = 'confirmed',
    float $total = 240
): Booking {
    return Booking::create([
        'booking_code' => 'PAY-'.fake()->unique()->numerify('#####'),
        'guest_id' => paymentApiGuest()->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-13',
        'adults' => 2,
        'children' => 0,
        'total_amount' => $total,
        'booking_source' => 'website',
        'status' => $status,
    ]);
}

it('requires authentication to create a payment', function () {
    $booking = paymentApiBooking();

    $response = $this->postJson('/api/v1/payments', [
        'booking_id' => $booking->id,
        'amount' => 100,
        'payment_method' => 'cash',
    ]);

    $response->assertUnauthorized();
});

it('creates a cash payment as paid', function () {
    $user = paymentApiUser();
    $booking = paymentApiBooking();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/payments', [
            'booking_id' => $booking->id,
            'amount' => 100,
            'payment_method' => 'cash',
            'transaction_id' => 'CASH-001',
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.booking_id', $booking->id)
        ->assertJsonPath('data.amount', '100.00')
        ->assertJsonPath('data.payment_method', 'cash')
        ->assertJsonPath('data.status', 'paid');

    $this->assertDatabaseHas('payments', [
        'booking_id' => $booking->id,
        'amount' => '100.00',
        'payment_method' => 'cash',
        'status' => 'paid',
    ]);
});

it('creates an online payment as pending', function () {
    $user = paymentApiUser();
    $booking = paymentApiBooking();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/payments', [
            'booking_id' => $booking->id,
            'amount' => 100,
            'payment_method' => 'online',
            'transaction_id' => 'ONLINE-001',
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.amount', '100.00')
        ->assertJsonPath('data.payment_method', 'online')
        ->assertJsonPath('data.status', 'pending');

    $this->assertDatabaseHas('payments', [
        'booking_id' => $booking->id,
        'transaction_id' => 'ONLINE-001',
        'status' => 'pending',
    ]);
});

it('does not allow payment amount above the remaining balance', function () {
    $user = paymentApiUser();
    $booking = paymentApiBooking();

    Payment::create([
        'booking_id' => $booking->id,
        'amount' => 100,
        'payment_method' => 'cash',
        'transaction_id' => 'PAID-001',
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/payments', [
            'booking_id' => $booking->id,
            'amount' => 200,
            'payment_method' => 'cash',
        ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.amount.0',
            'Payment amount cannot exceed the remaining balance of 140.'
        );

    expect(Payment::count())->toBe(1);
});

it('allows a payment equal to the remaining balance', function () {
    $user = paymentApiUser();
    $booking = paymentApiBooking();

    Payment::create([
        'booking_id' => $booking->id,
        'amount' => 100,
        'payment_method' => 'cash',
        'transaction_id' => 'PAID-002',
        'status' => 'paid',
        'paid_at' => now(),
    ]);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/payments', [
            'booking_id' => $booking->id,
            'amount' => 140,
            'payment_method' => 'cash',
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.amount', '140.00')
        ->assertJsonPath('data.status', 'paid');

    expect(Payment::count())->toBe(2);
});

it('rejects payment for a cancelled booking', function () {
    $user = paymentApiUser();
    $booking = paymentApiBooking('cancelled');

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/payments', [
            'booking_id' => $booking->id,
            'amount' => 100,
            'payment_method' => 'cash',
        ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.booking_id.0',
            'Payment cannot be created for a cancelled booking.'
        );
});

it('rejects payment for a completed booking', function () {
    $user = paymentApiUser();
    $booking = paymentApiBooking('completed');

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/payments', [
            'booking_id' => $booking->id,
            'amount' => 100,
            'payment_method' => 'cash',
        ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.booking_id.0',
            'Payment cannot be created for a completed booking.'
        );
});

it('rejects an invalid payment method', function () {
    $user = paymentApiUser();
    $booking = paymentApiBooking();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/payments', [
            'booking_id' => $booking->id,
            'amount' => 100,
            'payment_method' => 'crypto',
        ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.payment_method.0',
            'Invalid payment method.'
        );
});

it('rejects an invalid booking', function () {
    $user = paymentApiUser();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/payments', [
            'booking_id' => 999999,
            'amount' => 100,
            'payment_method' => 'cash',
        ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.booking_id.0',
            'The selected booking does not exist.'
        );
});

it('rejects a non positive payment amount', function () {
    $user = paymentApiUser();
    $booking = paymentApiBooking();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/payments', [
            'booking_id' => $booking->id,
            'amount' => 0,
            'payment_method' => 'cash',
        ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.amount.0',
            'Payment amount must be greater than zero.'
        );
});

it('rejects a duplicate transaction id', function () {
    $user = paymentApiUser();

    $booking1 = paymentApiBooking();
    $booking2 = paymentApiBooking();

    Payment::create([
        'booking_id' => $booking1->id,
        'amount' => 50,
        'payment_method' => 'online',
        'transaction_id' => 'TX-UNIQUE-001',
        'status' => 'pending',
    ]);

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/payments', [
            'booking_id' => $booking2->id,
            'amount' => 50,
            'payment_method' => 'online',
            'transaction_id' => 'TX-UNIQUE-001',
        ]);

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.transaction_id.0',
            'This transaction ID has already been used.'
        );
});
