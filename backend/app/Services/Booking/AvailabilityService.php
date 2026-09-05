<?php

namespace App\Services\Booking;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Room;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AvailabilityService
{
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

    public function getAvailabilityCalendar(
        string $checkIn,
        string $checkOut
    ): Collection {
        $rooms = Room::query()
            ->with(['roomType'])
            ->orderBy('room_number')
            ->get();

        $activeStatuses = ['pending', 'confirmed'];

        $occupiedRoomIds = Booking::query()
            ->whereIn('status', $activeStatuses)
            ->where('check_in', '<', $checkOut)
            ->where('check_out', '>', $checkIn)
            ->with(['bookingItems'])
            ->get()
            ->flatMap(fn (Booking $booking) => $booking->bookingItems->pluck('room_id'))
            ->unique()
            ->values();

        $dates = $this->buildDateRange($checkIn, $checkOut);

        return $rooms->map(function (Room $room) use (
            $occupiedRoomIds,
            $checkIn,
            $checkOut,
            $dates
        ) {
            $available = $room->status === 'available'
                && ! $occupiedRoomIds->contains($room->id);

            return [
                'room_id' => $room->id,
                'room_number' => $room->room_number,
                'room_type' => [
                    'id' => $room->roomType?->id,
                    'name' => $room->roomType?->name,
                ],
                'status' => $room->status,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'available' => $available,
                'dates' => $dates,
            ];
        });
    }

    public function checkOverlap(
        array $roomIds,
        string $checkIn,
        string $checkOut
    ): bool {
        return BookingItem::query()
            ->whereIn('room_id', $roomIds)
            ->whereHas('booking', function ($query) use (
                $checkIn,
                $checkOut
            ) {
                $query
                    ->whereIn('status', [
                        'pending',
                        'confirmed',
                    ])
                    ->where('check_in', '<', $checkOut)
                    ->where('check_out', '>', $checkIn);
            })
            ->exists();
    }

    private function buildDateRange(string $checkIn, string $checkOut): array
    {
        $start = Carbon::parse($checkIn)->startOfDay();
        $end = Carbon::parse($checkOut)->startOfDay();

        $dates = [];
        $cursor = $start->copy();
        $guard = 0;

        while ($cursor->lessThan($end) && $guard < 366) {
            $dates[] = $cursor->toDateString();
            $cursor->addDay();
            $guard++;
        }

        return $dates;
    }
}
