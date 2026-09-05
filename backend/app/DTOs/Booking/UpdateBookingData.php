<?php

namespace App\DTOs\Booking;

readonly class UpdateBookingData
{
    public function __construct(
        public ?string $checkIn = null,
        public ?string $checkOut = null,
        public ?int $adults = null,
        public ?int $children = null,
        public ?string $specialRequest = null,
        public ?int $couponId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            checkIn: $data['check_in'] ?? null,
            checkOut: $data['check_out'] ?? null,
            adults: isset($data['adults']) ? (int) $data['adults'] : null,
            children: isset($data['children']) ? (int) $data['children'] : null,
            specialRequest: $data['special_request'] ?? null,
            couponId: isset($data['coupon_id']) ? (int) $data['coupon_id'] : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'check_in' => $this->checkIn,
            'check_out' => $this->checkOut,
            'adults' => $this->adults,
            'children' => $this->children,
            'special_request' => $this->specialRequest,
            'coupon_id' => $this->couponId,
        ], fn ($v) => $v !== null);
    }
}
