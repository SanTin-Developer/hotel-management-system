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

function paymentStatusApiUser(string $role = 'manager'): User
{
    $user = User::factory()->create([
        'status' => 'active',
    ]);

    $user->assignRole($role);

    return $user;
}

function paymentStatusApiGuest(): Guest
{
    return Guest::create([
        'full_name' => 'Payment Status API Guest',
        'email' => fake()->unique()->safeEmail(),
        'phone' => '012345678',
        'country' => 'Cambodia',
    ]);
}

function paymentStatusApiBooking(): Booking
{
    return Booking::create([
        'booking_code' => 'PSAPI-'.fake()->unique()->numerify('#####'),
        'guest_id' => paymentStatusApiGuest()->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-13',
        'adults' => 2,
        'children' => 0,
        'total_amount' => 240,
        'booking_source' => 'website',
        'status' => 'confirmed',
    ]);
}

function paymentStatusApiPayment(string $status = 'pending'): Payment
{
    return Payment::create([
        'booking_id' => paymentStatusApiBooking()->id,
        'amount' => 240,
        'payment_method' => 'online',
        'transaction_id' => null,
        'status' => $status,
    ]);
}

it('requires authentication to mark payment as paid', function () {
    $payment = paymentStatusApiPayment();

    $response = $this->postJson(
        "/api/v1/payments/{$payment->id}/paid"
    );

    $response->assertUnauthorized();
});

it('marks a pending payment as paid', function () {
    $user = paymentStatusApiUser();
    $payment = paymentStatusApiPayment();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson(
            "/api/v1/payments/{$payment->id}/paid",
            [
                'transaction_id' => 'TX-PAID-API-001',
            ]
        );

    $response
        ->assertOk()
        ->assertJsonPath('data.id', $payment->id)
        ->assertJsonPath('data.status', 'paid')
        ->assertJsonPath(
            'data.transaction_id',
            'TX-PAID-API-001'
        );

    $this->assertDatabaseHas('payments', [
        'id' => $payment->id,
        'status' => 'paid',
        'transaction_id' => 'TX-PAID-API-001',
    ]);
});

it('marks a pending payment as failed', function () {
    $user = paymentStatusApiUser();
    $payment = paymentStatusApiPayment();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson(
            "/api/v1/payments/{$payment->id}/failed"
        );

    $response
        ->assertOk()
        ->assertJsonPath('data.status', 'failed');
});

it('refunds a paid payment', function () {
    $user = paymentStatusApiUser();
    $payment = paymentStatusApiPayment('paid');

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson(
            "/api/v1/payments/{$payment->id}/refund",
            [
                'transaction_id' => 'REFUND-API-001',
            ]
        );

    $response
        ->assertOk()
        ->assertJsonPath('data.status', 'refunded')
        ->assertJsonPath(
            'data.transaction_id',
            'REFUND-API-001'
        );
});

it('rejects marking a failed payment as paid', function () {
    $user = paymentStatusApiUser();
    $payment = paymentStatusApiPayment('failed');

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson(
            "/api/v1/payments/{$payment->id}/paid"
        );

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.status.0',
            'Cannot change payment status from failed to paid.'
        );
});

it('rejects refunding a pending payment', function () {
    $user = paymentStatusApiUser();
    $payment = paymentStatusApiPayment();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson(
            "/api/v1/payments/{$payment->id}/refund"
        );

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.status.0',
            'Cannot change payment status from pending to refunded.'
        );
});

it('rejects changing a refunded payment', function () {
    $user = paymentStatusApiUser();
    $payment = paymentStatusApiPayment('refunded');

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson(
            "/api/v1/payments/{$payment->id}/paid"
        );

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.status.0',
            'Cannot change payment status from refunded to paid.'
        );
});

it('returns 404 for a missing payment', function () {
    $user = paymentStatusApiUser();

    $response = $this
        ->actingAs($user, 'sanctum')
        ->postJson('/api/v1/payments/999999/paid');

    $response->assertNotFound();
});
