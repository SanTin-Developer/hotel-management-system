<?php

namespace App\Services\Booking;

use App\DTOs\Booking\CreateBookingData;
use App\Jobs\SendBookingConfirmationEmail;
use App\Jobs\SendBookingStatusEmail;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingStatusHistory;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Room;
use App\Repositories\BookingRepository;
use App\Services\Coupon\CouponService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function __construct(
        private readonly BookingRepository $bookingRepository,
        private readonly AvailabilityService $availabilityService,
        private readonly BookingPriceService $priceService,
        private readonly CouponService $couponService,
    ) {}

    public function getAll(array $filters = [])
    {
        return $this->bookingRepository->getAll($filters);
    }

    public function getAvailableRooms(
        string $checkIn,
        string $checkOut
    ): Collection {
        return $this->availabilityService->getAvailableRooms(
            $checkIn,
            $checkOut
        );
    }

    public function getAvailabilityCalendar(
        string $checkIn,
        string $checkOut
    ): Collection {
        return $this->availabilityService->getAvailabilityCalendar(
            $checkIn,
            $checkOut
        );
    }

    public function create(array $data): Booking
    {
        $dto = CreateBookingData::fromArray($data);

        return DB::transaction(function () use ($dto) {
            $roomIds = collect($dto->roomIds)
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->sort()
                ->values()
                ->all();

            $rooms = Room::query()
                ->with('roomType')
                ->whereIn('id', $roomIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($rooms->count() !== count($roomIds)) {
                throw ValidationException::withMessages([
                    'room_ids' => 'One or more selected rooms do not exist.',
                ]);
            }

            $unavailableRooms = $rooms
                ->filter(fn (Room $room) => $room->status !== 'available');

            if ($unavailableRooms->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'room_ids' => 'One or more selected rooms are not currently available.',
                ]);
            }

            $hasOverlap = $this->availabilityService->checkOverlap(
                $roomIds,
                $dto->checkIn,
                $dto->checkOut
            );

            if ($hasOverlap) {
                throw ValidationException::withMessages([
                    'room_ids' => 'One or more selected rooms are no longer available for the requested dates.',
                ]);
            }

            $checkIn = Carbon::parse($dto->checkIn);
            $checkOut = Carbon::parse($dto->checkOut);
            $nights = $checkIn->diffInDays($checkOut);

            $baseTotal = $this->priceService->calculateBaseTotal(
                $rooms,
                $nights
            );

            $couponResult = null;

            if ($dto->couponId) {
                $couponResult = $this->couponService->apply(
                    $dto->couponId,
                    $baseTotal
                );
            }

            $totalAmount = $this->priceService->calculateFinalTotal(
                $baseTotal,
                $couponResult
            );

            $guest = Guest::query()->whereKey($dto->guestId)->first();

            if (! $guest) {
                throw ValidationException::withMessages([
                    'guest_id' => 'The selected guest does not exist.',
                ]);
            }

            $depositRate = $this->depositRateForGuest($guest);
            $depositAmount = round($totalAmount * $depositRate / 100, 2);

            $booking = Booking::create([
                'booking_code' => $this->generateBookingCode(),
                'guest_id' => $dto->guestId,
                'check_in' => $dto->checkIn,
                'check_out' => $dto->checkOut,
                'adults' => $dto->adults,
                'children' => $dto->children,
                'total_amount' => $totalAmount,
                'deposit_rate' => $depositRate,
                'deposit_amount' => $depositAmount,
                'booking_source' => $dto->bookingSource,
                'created_by' => $dto->createdBy,
                'status' => 'pending',
                'special_request' => $dto->specialRequest,
                'coupon_id' => $dto->couponId,
            ]);

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'status' => 'pending',
                'changed_by' => $dto->createdBy,
                'note' => 'Booking created.',
            ]);

            foreach ($rooms as $room) {
                $pricePerNight = (float) $room->roomType->base_price;
                $subtotal = $this->priceService->calculateRoomSubtotal(
                    $pricePerNight,
                    $nights
                );

                BookingItem::create([
                    'booking_id' => $booking->id,
                    'room_id' => $room->id,
                    'price_per_night' => $pricePerNight,
                    'nights' => $nights,
                    'subtotal' => $subtotal,
                    'status' => 'reserved',
                ]);
            }

            $booking->load([
                'guest',
                'rooms.roomType',
                'bookingItems.room',
                'coupon',
            ]);

            return $booking;
        });
    }

    public function confirm(
        Booking $booking,
        ?int $changedBy = null,
        ?string $note = null
    ): Booking {
        return $this->changeStatus(
            $booking,
            'confirmed',
            $changedBy,
            $note
        );
    }

    public function cancel(
        Booking $booking,
        ?int $changedBy = null,
        ?string $note = null
    ): Booking {
        return $this->changeStatus(
            $booking,
            'cancelled',
            $changedBy,
            $note
        );
    }

    public function requestCancellation(
        Booking $booking,
        ?int $changedBy = null
    ): Booking {
        return DB::transaction(function () use ($booking, $changedBy) {
            $booking = Booking::query()
                ->whereKey($booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($booking->status, ['pending', 'confirmed'], true)) {
                throw ValidationException::withMessages([
                    'status' => [
                        'Only pending or confirmed bookings can request cancellation.',
                    ],
                ]);
            }

            if (! $this->isRefundEligibleAt($booking)) {
                throw ValidationException::withMessages([
                    'status' => [
                        'Cancellation requests are only accepted more than 48 hours before check-in.',
                    ],
                ]);
            }

            $fromStatus = $booking->status;

            $booking->update([
                'status' => 'cancellation_requested',
            ]);

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'status' => 'cancellation_requested',
                'changed_by' => $changedBy,
                'note' => "Cancellation requested from:{$fromStatus}.",
            ]);

            return $booking->refresh()->load([
                'guest',
                'rooms.roomType',
                'bookingItems.room',
                'statusHistories.changedBy',
                'coupon',
            ]);
        });
    }

    public function approveCancellation(
        Booking $booking,
        ?int $changedBy = null
    ): Booking {
        return DB::transaction(function () use ($booking, $changedBy) {
            $booking = Booking::query()
                ->whereKey($booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($booking->status !== 'cancellation_requested') {
                throw ValidationException::withMessages([
                    'status' => [
                        'Only cancellation requests can be approved.',
                    ],
                ]);
            }

            $booking->update([
                'status' => 'cancelled',
            ]);

            $this->refundDepositPayments($booking);

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'status' => 'cancelled',
                'changed_by' => $changedBy,
                'note' => 'Cancellation approved by hotel.',
            ]);

            SendBookingStatusEmail::dispatch(
                $booking->id,
                'cancelled',
                'Cancellation approved by hotel.'
            )->afterCommit();

            return $booking->refresh()->load([
                'guest',
                'rooms.roomType',
                'bookingItems.room',
                'statusHistories.changedBy',
                'coupon',
            ]);
        });
    }

    public function rejectCancellation(
        Booking $booking,
        ?int $changedBy = null
    ): Booking {
        return DB::transaction(function () use ($booking, $changedBy) {
            $booking = Booking::query()
                ->whereKey($booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($booking->status !== 'cancellation_requested') {
                throw ValidationException::withMessages([
                    'status' => [
                        'Only cancellation requests can be rejected.',
                    ],
                ]);
            }

            $restoreStatus = $this->priorStatusBeforeRequest($booking);

            $booking->update([
                'status' => $restoreStatus,
            ]);

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'status' => $restoreStatus,
                'changed_by' => $changedBy,
                'note' => 'Cancellation request rejected.',
            ]);

            return $booking->refresh()->load([
                'guest',
                'rooms.roomType',
                'bookingItems.room',
                'statusHistories.changedBy',
                'coupon',
            ]);
        });
    }

    public function checkIn(
        Booking $booking,
        ?int $changedBy = null,
        ?string $note = null
    ): Booking {
        return $this->changeStatus(
            $booking,
            'in_house',
            $changedBy,
            $note
        );
    }

    public function complete(
        Booking $booking,
        ?int $changedBy = null,
        ?string $note = null
    ): Booking {
        return $this->changeStatus(
            $booking,
            'completed',
            $changedBy,
            $note
        );
    }

    private function changeStatus(
        Booking $booking,
        string $newStatus,
        ?int $changedBy = null,
        ?string $note = null
    ): Booking {
        return DB::transaction(function () use (
            $booking,
            $newStatus,
            $changedBy,
            $note
        ) {
            $booking = Booking::query()
                ->whereKey($booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            $allowedTransactions = [
                'pending' => [
                    'confirmed',
                    'cancelled',
                    'cancellation_requested',
                ],

                'confirmed' => [
                    'in_house',
                    'completed',
                    'cancelled',
                    'cancellation_requested',
                ],

                'in_house' => [
                    'completed',
                    'cancelled',
                ],

                'cancellation_requested' => [
                    'cancelled',
                ],

                'completed' => [],
                'cancelled' => [],
            ];

            $currentStatus = $booking->status;

            if (
                ! in_array(
                    $newStatus,
                    $allowedTransactions[$currentStatus] ?? [],
                    true
                )
            ) {
                throw ValidationException::withMessages([
                    'status' => [
                        "Cannot change booking status from {$currentStatus} to {$newStatus}.",
                    ],
                ]);
            }

            $booking->update([
                'status' => $newStatus,
            ]);

            if ($newStatus === 'cancelled' && $this->isRefundEligibleAt($booking)) {
                $this->refundDepositPayments($booking);
            }

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'status' => $newStatus,
                'changed_by' => $changedBy,
                'note' => $note,
            ]);

            if ($newStatus === 'confirmed') {
                SendBookingConfirmationEmail::dispatch($booking->id)
                    ->afterCommit();
            } else {
                SendBookingStatusEmail::dispatch(
                    $booking->id,
                    $newStatus,
                    $note
                )->afterCommit();
            }

            return $booking->refresh()->load([
                'guest',
                'rooms.roomType',
                'bookingItems.room',
                'statusHistories.changedBy',
                'coupon',
            ]);
        });
    }

    private function generateBookingCode(): string
    {
        do {
            $code = 'BK-'.
                now()->format('Ymd').
                '-'.
                Str::upper(Str::random(6));
        } while (
            Booking::where('booking_code', $code)->exists()
        );

        return $code;
    }

    private function depositRateForGuest(Guest $guest): float
    {
        $country = strtolower(
            trim((string) ($guest->country ?? $guest->nationality ?? ''))
        );

        if (
            str_contains($country, 'cambodia')
            || str_contains($country, 'khmer')
        ) {
            return 20.0;
        }

        return 30.0;
    }

    private function isRefundEligibleAt(Booking $booking): bool
    {
        $checkIn = $booking->check_in;

        if (! $checkIn) {
            return false;
        }

        $deadline = $checkIn->copy()->startOfDay()->subHours(48);

        return now()->lt($deadline);
    }

    private function refundDepositPayments(Booking $booking): void
    {
        $booking->payments()
            ->where('status', 'paid')
            ->get()
            ->each(function (Payment $payment) {
                $payment->update([
                    'status' => 'refunded',
                    'transaction_id' => $payment->transaction_id
                        ?: 'RFD-'.$payment->id.'-'.now()->format('Ymd'),
                ]);
            });
    }

    private function priorStatusBeforeRequest(Booking $booking): string
    {
        $entry = $booking->statusHistories()
            ->where('status', 'cancellation_requested')
            ->orderByDesc('id')
            ->first();

        if ($entry && preg_match('/from:(\w+)/', (string) $entry->note, $matches)) {
            $from = $matches[1];

            if (in_array($from, ['pending', 'confirmed'], true)) {
                return $from;
            }
        }

        return 'confirmed';
    }
}
