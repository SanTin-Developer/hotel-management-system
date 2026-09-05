<?php

namespace App\Repositories;

use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardRepository
{
    public function getRoomStats(): object
    {
        return Room::query()
            ->selectRaw("
                COUNT(*) AS total_rooms,
                COUNT(*) FILTER (
                    WHERE status = 'available'
                ) AS available_rooms,
                COUNT(*) FILTER (
                    WHERE status = 'occupied'
                ) AS occupied_rooms
            ")
            ->first();
    }

    public function getTodayBookingsCount(): int
    {
        return Booking::query()
            ->whereDate('created_at', Carbon::today())
            ->count();
    }

    public function getTotalGuestsCount(): int
    {
        return Guest::query()->count();
    }

    public function getRevenueStats(Carbon $today): object
    {
        return Payment::query()
            ->selectRaw("
                COALESCE(
                    SUM(
                        CASE
                            WHEN status = 'paid'
                            THEN amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS total_revenue,

                COALESCE(
                    SUM(
                        CASE
                            WHEN status = 'paid'
                            AND paid_at::date = ?
                            THEN amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS today_revenue
            ", [$today->toDateString()])
            ->first();
    }

    public function getRevenueByDay(
        Carbon $from,
        Carbon $to
    ): Collection {
        return Payment::query()
            ->selectRaw(
                'DATE(paid_at) AS date, SUM(amount) AS revenue'
            )
            ->where('status', 'paid')
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [
                $from,
                $to,
            ])
            ->groupByRaw('DATE(paid_at)')
            ->orderBy('date')
            ->get()
            ->keyBy('date');
    }

    public function getRevenueByMonth(
        Carbon $from,
        Carbon $to
    ): Collection {
        return Payment::query()
            ->selectRaw("
                DATE_TRUNC('month', paid_at) AS month,
                SUM(amount) AS revenue
            ")
            ->where('status', 'paid')
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [
                $from,
                $to,
            ])
            ->groupByRaw("DATE_TRUNC('month', paid_at)")
            ->orderBy('month')
            ->get();
    }

    public function getBookingOverview(): array
    {
        $rows = Booking::query()
            ->selectRaw('
                status,
                COUNT(*) AS total
            ')
            ->whereIn('status', [
                'confirmed',
                'pending',
                'cancelled',
                'completed',
            ])
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status');

        return [
            'confirmed' => (int) ($rows['confirmed'] ?? 0),
            'pending' => (int) ($rows['pending'] ?? 0),
            'cancelled' => (int) ($rows['cancelled'] ?? 0),
            'completed' => (int) ($rows['completed'] ?? 0),
        ];
    }

    public function getRecentBookings(): Collection
    {
        return Booking::query()
            ->with([
                'guest:id,full_name,email',
                'rooms:id,room_number',
            ])
            ->latest()
            ->limit(10)
            ->get([
                'id',
                'booking_code',
                'guest_id',
                'check_in',
                'check_out',
                'total_amount',
                'status',
                'created_at',
            ]);
    }

    public function getRecentActivities(): Collection
    {
        return BookingStatusHistory::query()
            ->with([
                'booking:id,booking_code',
                'changedBy:id,name',
            ])
            ->latest('created_at')
            ->limit(10)
            ->get([
                'id',
                'booking_id',
                'status',
                'changed_by',
                'note',
                'created_at',
            ]);
    }

    public function getRoomStatus(): array
    {
        $rows = Room::query()
            ->selectRaw('
                status,
                COUNT(*) AS total
            ')
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status');

        return [
            'available' => (int) ($rows['available'] ?? 0),
            'occupied' => (int) ($rows['occupied'] ?? 0),
            'maintenance' => (int) ($rows['maintenance'] ?? 0),
            'cleaning' => (int) ($rows['cleaning'] ?? 0),
            'out_of_service' => (int) ($rows['out_of_service'] ?? 0),
        ];
    }
}
