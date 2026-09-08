<?php

use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::insert([
        ['name' => 'bookings.create', 'guard_name' => 'web'],
        ['name' => 'bookings.cancel', 'guard_name' => 'web'],
        ['name' => 'bookings.confirm', 'guard_name' => 'web'],
        ['name' => 'bookings.complete', 'guard_name' => 'web'],
    ]);

    $manager = Role::create(['name' => 'manager', 'guard_name' => 'web']);
    $customer = Role::create(['name' => 'customer', 'guard_name' => 'web']);

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

function cancellationUser(string $role = 'manager'): User
{
    $user = User::factory()->create(['status' => 'active']);
    $user->assignRole($role);

    return $user;
}

function cancellationCustomer(): array
{
    $customer = User::factory()->create([
        'status' => 'active',
        'email' => 'canceller@example.com',
    ]);

    $customer->assignRole('customer');

    $guest = Guest::create([
        'full_name' => 'Cancellation Guest',
        'email' => 'canceller@example.com',
        'phone' => '012345678',
        'country' => 'Cambodia',
    ]);

    return [$customer, $guest];
}

function cancellationBooking(Guest $guest, string $status = 'confirmed', ?string $checkIn = null): Booking
{
    $booking = Booking::create([
        'booking_code' => 'CAN-'.fake()->unique()->numerify('#####'),
        'guest_id' => $guest->id,
        'check_in' => $checkIn ?? now()->addDays(10)->toDateString(),
        'check_out' => now()->addDays(13)->toDateString(),
        'adults' => 2,
        'children' => 0,
        'total_amount' => 240,
        'deposit_rate' => 20,
        'deposit_amount' => 48,
        'booking_source' => 'website',
        'status' => $status,
    ]);

    return $booking;
}

function addPaidDeposit(Booking $booking): Payment
{
    return Payment::create([
        'booking_id' => $booking->id,
        'amount' => $booking->deposit_amount,
        'payment_method' => 'aba',
        'transaction_id' => 'TXN-'.fake()->unique()->numerify('#######'),
        'status' => 'paid',
        'paid_at' => now(),
    ]);
}

it('allows a customer to request cancellation more than 48 hours before check-in', function () {
    [$customer, $guest] = cancellationCustomer();
    $booking = cancellationBooking($guest, 'confirmed');

    $response = $this
        ->actingAs($customer, 'sanctum')
        ->postJson("/api/v1/bookings/{$booking->id}/request-cancellation");

    $response
        ->assertOk()
        ->assertJsonPath('data.status', 'cancellation_requested');

    $this->assertDatabaseHas('booking_status_histories', [
        'booking_id' => $booking->id,
        'status' => 'cancellation_requested',
        'changed_by' => $customer->id,
    ]);
});

it('rejects a cancellation request within 48 hours of check-in', function () {
    [$customer, $guest] = cancellationCustomer();
    $booking = cancellationBooking($guest, 'confirmed', now()->addDay()->toDateString());

    $response = $this
        ->actingAs($customer, 'sanctum')
        ->postJson("/api/v1/bookings/{$booking->id}/request-cancellation");

    $response
        ->assertStatus(422)
        ->assertJsonPath(
            'errors.status.0',
            'Cancellation requests are only accepted more than 48 hours before check-in.'
        );

    $this->assertDatabaseHas('bookings', [
        'id' => $booking->id,
        'status' => 'confirmed',
    ]);
});

it('rejects a cancellation request for a non refundable completed booking', function () {
    [$customer, $guest] = cancellationCustomer();
    $booking = cancellationBooking($guest, 'completed');

    $response = $this
        ->actingAs($customer, 'sanctum')
        ->postJson("/api/v1/bookings/{$booking->id}/request-cancellation");

    $response->assertStatus(422);
});

it('approves a cancellation request and refunds the paid deposit when eligible', function () {
    [$customer, $guest] = cancellationCustomer();
    $booking = cancellationBooking($guest, 'cancellation_requested');
    $payment = addPaidDeposit($booking);

    $manager = cancellationUser();

    $response = $this
        ->actingAs($manager, 'sanctum')
        ->postJson("/api/v1/bookings/{$booking->id}/approve-cancellation");

    $response
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');

    $this->assertDatabaseHas('payments', [
        'id' => $payment->id,
        'status' => 'refunded',
    ]);
});

it('refunds the deposit when a cancellation is approved within 48 hours of check-in', function () {
    $manager = cancellationUser();

    [$customer, $guest] = cancellationCustomer();
    $booking = cancellationBooking(
        $guest,
        'cancellation_requested',
        now()->addDay()->toDateString()
    );
    $payment = addPaidDeposit($booking);

    $response = $this
        ->actingAs($manager, 'sanctum')
        ->postJson("/api/v1/bookings/{$booking->id}/approve-cancellation");

    $response
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');

    $this->assertDatabaseHas('payments', [
        'id' => $payment->id,
        'status' => 'refunded',
    ]);
});

it('rejects a cancellation request and restores the previous status', function () {
    $manager = cancellationUser();

    [$customer, $guest] = cancellationCustomer();
    $booking = cancellationBooking($guest, 'confirmed');

    BookingStatusHistory::create([
        'booking_id' => $booking->id,
        'status' => 'cancellation_requested',
        'note' => 'Cancellation requested from:confirmed.',
    ]);

    $booking->update(['status' => 'cancellation_requested']);

    $response = $this
        ->actingAs($manager, 'sanctum')
        ->postJson("/api/v1/bookings/{$booking->id}/reject-cancellation");

    $response
        ->assertOk()
        ->assertJsonPath('data.status', 'confirmed');

    $this->assertDatabaseHas('booking_status_histories', [
        'booking_id' => $booking->id,
        'status' => 'confirmed',
        'note' => 'Cancellation request rejected.',
    ]);
});

it('refunds a paid deposit when a manager cancels more than 48 hours before check-in', function () {
    $manager = cancellationUser();
    [$customer, $guest] = cancellationCustomer();
    $booking = cancellationBooking($guest, 'confirmed');
    $payment = addPaidDeposit($booking);

    $response = $this
        ->actingAs($manager, 'sanctum')
        ->postJson("/api/v1/bookings/{$booking->id}/cancel");

    $response
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');

    $this->assertDatabaseHas('payments', [
        'id' => $payment->id,
        'status' => 'refunded',
    ]);
});

it('does not refund a paid deposit when a manager cancels within 48 hours of check-in', function () {
    $manager = cancellationUser();
    [$customer, $guest] = cancellationCustomer();
    $booking = cancellationBooking($guest, 'confirmed', now()->addDay()->toDateString());
    $payment = addPaidDeposit($booking);

    $response = $this
        ->actingAs($manager, 'sanctum')
        ->postJson("/api/v1/bookings/{$booking->id}/cancel");

    $response
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');

    $this->assertDatabaseHas('payments', [
        'id' => $payment->id,
        'status' => 'paid',
    ]);
});

it('exposes cancellation refundability on the booking resource', function () {
    [$customer, $guest] = cancellationCustomer();
    $booking = cancellationBooking($guest, 'confirmed');

    $response = $this
        ->actingAs($customer, 'sanctum')
        ->getJson('/api/v1/bookings?guest_id='.$guest->id);

    $response
        ->assertOk()
        ->assertJsonPath('data.0.cancellation_refundable', true)
        ->assertJsonStructure([
            'data' => [
                ['cancel_request_deadline'],
            ],
        ]);
});