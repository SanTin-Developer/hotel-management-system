<?php

namespace App\DTOs\Booking;

readonly class CreateBookingData
{
    public function __construct(
        public int $guestId,
        public string $checkIn,
        public string $checkOut,
        public int $adults,
        public int $children,
        public array $roomIds,
        public ?int $couponId = null,
        public ?string $specialRequest = null,
        public ?string $bookingSource = 'website',
        public ?int $createdBy = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            guestId: (int) $data['guest_id'],
            checkIn: $data['check_in'],
            checkOut: $data['check_out'],
            adults: (int) $data['adults'],
            children: (int) ($data['children'] ?? 0),
            roomIds: (array) $data['room_ids'],
            couponId: isset($data['coupon_id']) ? (int) $data['coupon_id'] : null,
            specialRequest: $data['special_request'] ?? null,
            bookingSource: $data['booking_source'] ?? 'website',
            createdBy: isset($data['created_by']) ? (int) $data['created_by'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'guest_id' => $this->guestId,
            'check_in' => $this->checkIn,
            'check_out' => $this->checkOut,
            'adults' => $this->adults,
            'children' => $this->children,
            'room_ids' => $this->roomIds,
            'coupon_id' => $this->couponId,
            'special_request' => $this->specialRequest,
            'booking_source' => $this->bookingSource,
            'created_by' => $this->createdBy,
        ];
    }
}
