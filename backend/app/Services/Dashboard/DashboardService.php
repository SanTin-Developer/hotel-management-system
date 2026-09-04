<?php

namespace App\Services\Dashboard;

use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public function summary(): array
    {
        return Cache::store('redis')->remember(
            'dashboard:summary',
            now()->addSeconds(60),
            fn () => [
                'stats' => $this->stats(),
                'revenue_chart' => $this->revenueChart(),
                'booking_overview' => $this->bookingOverview(),
                'recent_bookings' => $this->recentBookings(),
                'recent_activities' => $this->recentActivities(),
                'room_status' => $this->roomStatus(),
            ]
        );
    }

    public function clearCache(): void
    {
        Cache::store('redis')->forget('dashboard:summary');
    }

    private function stats(): array
    {
        $today = Carbon::today();

        $roomStats = Room::query()
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

        $todayBookings = Booking::query()
            ->whereDate('created_at', $today)
            ->count();

        $totalGuests = Guest::query()->count();

        $revenue = Payment::query()
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

        return [
            'total_rooms' => (int) $roomStats->total_rooms,
            'today_bookings' => $todayBookings,
            'total_guests' => $totalGuests,
            'today_revenue' => (float) $revenue->today_revenue,
            'total_revenue' => (float) $revenue->total_revenue,
            'available_rooms' => (int) $roomStats->available_rooms,
            'occupied_rooms' => (int) $roomStats->occupied_rooms,
        ];
    }

    private function revenueChart(): array
    {
        $now = Carbon::now();

        return [
            'today' => $this->revenueByDay(
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay()
            ),

            'this_week' => $this->revenueByDay(
                $now->copy()->startOfWeek(),
                $now->copy()->endOfWeek()
            ),

            'this_month' => $this->revenueByDay(
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth()
            ),

            'this_year' => $this->revenueByMonth(
                $now->copy()->startOfYear(),
                $now->copy()->endOfYear()
            ),
        ];
    }

    private function revenueByDay(
        Carbon $from,
        Carbon $to
    ): array {
        $rows = Payment::query()
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

        $result = [];

        for (
            $date = $from->copy()->startOfDay();
            $date->lte($to);
            $date->addDay()
        ) {
            $key = $date->toDateString();

            $result[] = [
                'label' => $date->format('M d'),
                'date' => $key,
                'revenue' => (float) ($rows[$key]->revenue ?? 0),
            ];
        }

        return $result;
    }

    private function revenueByMonth(
        Carbon $from,
        Carbon $to
    ): array {
        $rows = Payment::query()
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

        $indexed = $rows->keyBy(
            fn ($row) => Carbon::parse($row->month)->format('Y-m')
        );

        $result = [];

        for (
            $month = $from->copy()->startOfMonth();
            $month->lte($to);
            $month->addMonth()
        ) {
            $key = $month->format('Y-m');

            $result[] = [
                'label' => $month->format('M'),
                'month' => $key,
                'revenue' => (float) ($indexed[$key]->revenue ?? 0),
            ];
        }

        return $result;
    }

    private function bookingOverview(): array
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

    private function recentBookings(): Collection
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

    private function recentActivities(): Collection
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

    private function roomStatus(): array
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
