<?php

namespace App\Services\Booking;

use App\DTOs\Booking\CreateBookingData;
use App\Jobs\SendBookingConfirmationEmail;
use App\Jobs\SendBookingStatusEmail;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingStatusHistory;
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

            $booking = Booking::create([
                'booking_code' => $this->generateBookingCode(),
                'guest_id' => $dto->guestId,
                'check_in' => $dto->checkIn,
                'check_out' => $dto->checkOut,
                'adults' => $dto->adults,
                'children' => $dto->children,
                'total_amount' => $totalAmount,
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

            SendBookingConfirmationEmail::dispatch($booking->id)
                ->afterCommit();

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
                ],

                'confirmed' => [
                    'completed',
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

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'status' => $newStatus,
                'changed_by' => $changedBy,
                'note' => $note,
            ]);

            SendBookingStatusEmail::dispatch(
                $booking->id,
                $newStatus,
                $note
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
}
