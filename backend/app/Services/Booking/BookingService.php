<?php

namespace App\Services\Booking;

use App\Jobs\SendBookingConfirmationEmail;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingStatusHistory;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function getAll(array $filters = [])
    {
        return Booking::query()
            ->with([
                'guest:id,full_name,email,phone',
                'rooms:id,room_number',
                'bookingItems.room:id,room_number',
            ])
            ->when(
                ! empty($filters['search']),
                function ($query) use ($filters) {
                    $search = $filters['search'];

                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('booking_code', 'ILIKE', "%{$search}%")
                            ->orWhereHas('guest', function ($query) use ($search) {
                                $query
                                    ->where('full_name', 'ILIKE', "%{$search}%")
                                    ->orWhere('email', 'ILIKE', "%{$search}%")
                                    ->orWhere('phone', 'ILIKE', "%{$search}%");
                            });
                    });
                }
            )
            ->when(
                ! empty($filters['status']),
                fn ($query) => $query->where(
                    'status',
                    $filters['status']
                )
            )
            ->when(
                ! empty($filters['guest_id']),
                fn ($query) => $query->where(
                    'guest_id',
                    $filters['guest_id']
                )
            )
            ->when(
                ! empty($filters['check_in']),
                fn ($query) => $query->whereDate(
                    'check_in',
                    '>=',
                    $filters['check_in']
                )
            )
            ->when(
                ! empty($filters['check_out']),
                fn ($query) => $query->whereDate(
                    'check_out',
                    '<=',
                    $filters['check_out']
                )
            )
            ->latest('created_at')
            ->paginate($filters['per_page'] ?? 20);
    }

    public function getAvailableRooms(
        string $checkIn,
        string $checkOut
    ): Collection {
        $activeStatuses = [
            'pending',
            'confirmed',
        ];

        return Room::query()
            ->with([
                'roomType',
                'amenities',
            ])
            ->withCount('bookingItems')
            ->where('status', 'available')
            ->whereDoesntHave('bookingItems.booking', function ($query) use (
                $checkIn,
                $checkOut,
                $activeStatuses
            ) {
                $query
                    ->whereIn('status', $activeStatuses)
                    ->where('check_in', '<', $checkOut)
                    ->where('check_out', '>', $checkIn);
            })
            ->orderBy('room_number')
            ->get();
    }

    /**
     * Create a booking safely against concurrent requests.
     */
    public function create(array $data): Booking
    {
        return DB::transaction(function () use ($data) {
            $roomIds = collect($data['room_ids'])
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->sort()
                ->values()
                ->all();

            /*
             * Lock the selected room rows.
             *
             * Sorting IDs before locking helps ensure concurrent
             * transactions acquire locks in the same order.
             */
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

            /*
             * Rooms must currently be bookable.
             */
            $unavailableRooms = $rooms
                ->filter(fn (Room $room) => $room->status !== 'available');

            if ($unavailableRooms->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'room_ids' => 'One or more selected rooms are not currently available.',
                ]);
            }

            /*
             * Re-check overlapping bookings while the room rows
             * are locked.
             *
             * This is the important second availability check.
             */
            $hasOverlap = BookingItem::query()
                ->whereIn('room_id', $roomIds)
                ->whereHas('booking', function ($query) use ($data) {
                    $query
                        ->whereIn('status', [
                            'pending',
                            'confirmed',
                        ])
                        ->where(
                            'check_in',
                            '<',
                            $data['check_out']
                        )
                        ->where(
                            'check_out',
                            '>',
                            $data['check_in']
                        );
                })
                ->exists();

            if ($hasOverlap) {
                throw ValidationException::withMessages([
                    'room_ids' => 'One or more selected rooms are no longer available for the requested dates.',
                ]);
            }

            $checkIn = Carbon::parse($data['check_in']);
            $checkOut = Carbon::parse($data['check_out']);

            $nights = $checkIn->diffInDays($checkOut);

            /*
             * Calculate the total on the backend.
             */
            $totalAmount = $rooms->sum(
                fn (Room $room) => (float) $room->roomType->base_price * $nights
            );

            $booking = Booking::create([
                'booking_code' => $this->generateBookingCode(),
                'guest_id' => $data['guest_id'],
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
                'adults' => $data['adults'],
                'children' => $data['children'] ?? 0,
                'total_amount' => $totalAmount,
                'booking_source' => $data['booking_source'] ?? 'website',
                'created_by' => $data['created_by'] ?? null,
                'status' => 'pending',
                'special_request' => $data['special_request'] ?? null,
            ]);

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'status' => 'pending',
                'changed_by' => $data['created_by'] ?? null,
                'note' => 'Booking created.',
            ]);

            foreach ($rooms as $room) {
                $pricePerNight = (float) $room->roomType->base_price;
                $subtotal = $pricePerNight * $nights;

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

            return $booking->refresh()->load([
                'guest',
                'rooms.roomType',
                'bookingItems.room',
                'statusHistories.changedBy',
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
