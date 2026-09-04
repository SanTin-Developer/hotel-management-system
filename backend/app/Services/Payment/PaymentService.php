<?php

namespace App\Services\Payment;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function create(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            $booking = Booking::query()
                ->with('payments')
                ->whereKey($data['booking_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($booking->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'booking_id' => [
                        'Payment cannot be created for a cancelled booking.',
                    ],
                ]);
            }

            if ($booking->status === 'completed') {
                throw ValidationException::withMessages([
                    'booking_id' => [
                        'Payment cannot be created for a completed booking.',
                    ],
                ]);
            }

            if (! empty($data['transaction_id'])) {
                $transactionExists = Payment::query()
                    ->where('transaction_id', $data['transaction_id'])
                    ->exists();

                if ($transactionExists) {
                    throw ValidationException::withMessages([
                        'transaction_id' => [
                            'This transaction ID has already been used.',
                        ],
                    ]);
                }
            }

            $paidAmount = (float) $booking->payments
                ->whereIn('status', ['pending', 'paid'])
                ->sum('amount');

            $bookingTotal = (float) $booking->total_amount;
            $paymentAmount = (float) $data['amount'];

            $remaining = round(
                $bookingTotal - $paidAmount,
                2
            );

            if ($paymentAmount > $remaining) {
                throw ValidationException::withMessages([
                    'amount' => [
                        "Payment amount cannot exceed the remaining balance of {$remaining}.",
                    ],
                ]);
            }

            $status = $data['payment_method'] === 'cash'
                ? 'paid'
                : 'pending';

            $payment = Payment::create([
                'booking_id' => $booking->id,
                'amount' => $paymentAmount,
                'payment_method' => $data['payment_method'],
                'transaction_id' => $data['transaction_id'] ?? null,
                'status' => $status,
                'paid_at' => $status === 'paid' ? now() : null,
            ]);

            return $payment->load('booking');
        });
    }

    public function markPaid(
        Payment $payment,
        ?string $transactionId = null
    ): Payment {
        return $this->changeStatus(
            $payment,
            'paid',
            $transactionId
        );
    }

    public function markFailed(Payment $payment): Payment
    {
        return $this->changeStatus($payment, 'failed');
    }

    public function refund(
        Payment $payment,
        ?string $transactionId = null
    ): Payment {
        return $this->changeStatus(
            $payment,
            'refunded',
            $transactionId
        );
    }

    private function changeStatus(
        Payment $payment,
        string $newStatus,
        ?string $transactionId = null
    ): Payment {
        return DB::transaction(function () use (
            $payment,
            $newStatus,
            $transactionId
        ) {
            $payment = Payment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $allowedTransitions = [
                'pending' => [
                    'paid',
                    'failed',
                ],

                'failed' => [],

                'paid' => [
                    'refunded',
                ],

                'refunded' => [],
            ];

            $currentStatus = $payment->status;

            if (
                ! in_array(
                    $newStatus,
                    $allowedTransitions[$currentStatus] ?? [],
                    true
                )
            ) {
                throw ValidationException::withMessages([
                    'status' => [
                        "Cannot change payment status from {$currentStatus} to {$newStatus}.",
                    ],
                ]);
            }

            $data = [
                'status' => $newStatus,
            ];

            if ($newStatus === 'paid') {
                $data['paid_at'] = now();

                if ($transactionId !== null) {
                    $data['transaction_id'] = $transactionId;
                }
            }

            if ($newStatus === 'refunded' && $transactionId !== null) {
                $data['transaction_id'] = $transactionId;
            }

            $payment->update($data);

            return $payment->refresh()->load('booking');
        });
    }
}
