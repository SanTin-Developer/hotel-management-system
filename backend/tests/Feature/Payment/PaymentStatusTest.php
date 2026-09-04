<?php

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Payment;
use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function paymentStatusGuest(): Guest
{
    return Guest::create([
        'full_name' => 'Payment Status Guest',
        'email' => fake()->unique()->safeEmail(),
        'phone' => '012345678',
        'country' => 'Cambodia',
    ]);
}

function paymentStatusBooking(): Booking
{
    return Booking::create([
        'booking_code' => 'STATUS-PAY-'.fake()->unique()->numerify('#####'),
        'guest_id' => paymentStatusGuest()->id,
        'check_in' => '2026-09-10',
        'check_out' => '2026-09-13',
        'adults' => 2,
        'children' => 0,
        'total_amount' => 240,
        'booking_source' => 'website',
        'status' => 'confirmed',
    ]);
}

function paymentStatusPayment(string $status = 'pending'): Payment
{
    return Payment::create([
        'booking_id' => paymentStatusBooking()->id,
        'amount' => 240,
        'payment_method' => 'online',
        'transaction_id' => null,
        'status' => $status,
        'paid_at' => null,
    ]);
}

it('marks a pending payment as paid', function () {
    $payment = paymentStatusPayment();

    $updated = app(PaymentService::class)->markPaid(
        $payment,
        'TX-PAID-001'
    );

    expect($updated->status)->toBe('paid');
    expect($updated->transaction_id)->toBe('TX-PAID-001');
    expect($updated->paid_at)->not->toBeNull();

    $this->assertDatabaseHas('payments', [
        'id' => $payment->id,
        'status' => 'paid',
        'transaction_id' => 'TX-PAID-001',
    ]);
});

it('marks a pending payment as failed', function () {
    $payment = paymentStatusPayment();

    $updated = app(PaymentService::class)->markFailed($payment);

    expect($updated->status)->toBe('failed');
    expect($updated->paid_at)->toBeNull();
});

it('refunds a paid payment', function () {
    $payment = paymentStatusPayment('paid');

    $updated = app(PaymentService::class)->refund(
        $payment,
        'REFUND-001'
    );

    expect($updated->status)->toBe('refunded');
    expect($updated->transaction_id)->toBe('REFUND-001');
});

it('cannot mark a failed payment as paid', function () {
    $payment = paymentStatusPayment('failed');

    expect(fn () => app(PaymentService::class)->markPaid($payment))
        ->toThrow(ValidationException::class);
});

it('cannot refund a pending payment', function () {
    $payment = paymentStatusPayment('pending');

    expect(fn () => app(PaymentService::class)->refund($payment))
        ->toThrow(ValidationException::class);
});

it('cannot fail a paid payment', function () {
    $payment = paymentStatusPayment('paid');

    expect(fn () => app(PaymentService::class)->markFailed($payment))
        ->toThrow(ValidationException::class);
});

it('cannot change a refunded payment', function () {
    $payment = paymentStatusPayment('refunded');

    expect(fn () => app(PaymentService::class)->markPaid($payment))
        ->toThrow(ValidationException::class);
});
