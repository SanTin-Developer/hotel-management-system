<?php

namespace App\Services\Payment;

use App\DTOs\Payment\CreatePaymentData;
use App\Models\Payment;
use App\Repositories\PaymentRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private readonly PaymentRepository $paymentRepository
    ) {}

    public function create(array $data): Payment
    {
        $dto = CreatePaymentData::fromArray($data);

        return DB::transaction(function () use ($dto) {
            $booking = $this->paymentRepository->getBookingWithPayments(
                $dto->bookingId
            );

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

            if (! empty($dto->transactionId)) {
                if ($this->paymentRepository->isTransactionUsed($dto->transactionId)) {
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
            $paymentAmount = $dto->amount;

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

            $status = $dto->paymentMethod === 'cash'
                ? 'paid'
                : 'pending';

            $payment = Payment::create([
                'booking_id' => $booking->id,
                'amount' => $paymentAmount,
                'payment_method' => $dto->paymentMethod,
                'transaction_id' => $dto->transactionId,
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
            $payment = $this->paymentRepository->findLocked($payment->id);

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

            $updateData = [
                'status' => $newStatus,
            ];

            if ($newStatus === 'paid') {
                $updateData['paid_at'] = now();

                if ($transactionId !== null) {
                    $updateData['transaction_id'] = $transactionId;
                }
            }

            if ($newStatus === 'refunded' && $transactionId !== null) {
                $updateData['transaction_id'] = $transactionId;
            }

            $payment->update($updateData);

            return $payment->refresh()->load('booking');
        });
    }
}
