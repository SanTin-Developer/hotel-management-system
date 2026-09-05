<?php

namespace App\DTOs\Payment;

readonly class CreatePaymentData
{
    public function __construct(
        public int $bookingId,
        public float $amount,
        public string $paymentMethod,
        public ?string $transactionId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            bookingId: (int) $data['booking_id'],
            amount: (float) $data['amount'],
            paymentMethod: $data['payment_method'],
            transactionId: $data['transaction_id'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'booking_id' => $this->bookingId,
            'amount' => $this->amount,
            'payment_method' => $this->paymentMethod,
            'transaction_id' => $this->transactionId,
        ];
    }
}
