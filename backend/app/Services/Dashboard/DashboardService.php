<?php

namespace App\Services\Dashboard;

use App\Repositories\DashboardRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public function __construct(
        private readonly DashboardRepository $dashboardRepository
    ) {}

    public function summary(): array
    {
        return Cache::store('redis')->remember(
            'dashboard:summary',
            now()->addSeconds(60),
            fn () => [
                'stats' => $this->stats(),
                'revenue_chart' => $this->revenueChart(),
                'booking_overview' => $this->dashboardRepository->getBookingOverview(),
                'recent_bookings' => $this->dashboardRepository->getRecentBookings(),
                'recent_activities' => $this->dashboardRepository->getRecentActivities(),
                'room_status' => $this->dashboardRepository->getRoomStatus(),
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

        $roomStats = $this->dashboardRepository->getRoomStats();
        $todayBookings = $this->dashboardRepository->getTodayBookingsCount();
        $totalGuests = $this->dashboardRepository->getTotalGuestsCount();
        $revenue = $this->dashboardRepository->getRevenueStats($today);

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
        $rows = $this->dashboardRepository->getRevenueByDay($from, $to);

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
        $rows = $this->dashboardRepository->getRevenueByMonth($from, $to);

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
}
