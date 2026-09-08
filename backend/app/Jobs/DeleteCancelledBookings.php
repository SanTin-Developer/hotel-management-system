<?php

namespace App\Jobs;

use App\Models\Booking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteCancelledBookings implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public const RETENTION_DAYS = 7;

    public function handle(): void
    {
        $cutoff = now()->subDays(self::RETENTION_DAYS);

        $ids = Booking::query()
            ->where('status', 'cancelled')
            ->where('updated_at', '<=', $cutoff)
            ->pluck('id');

        $count = $ids->count();

        if ($count === 0) {
            return;
        }

        DB::transaction(function () use ($ids) {
            Booking::query()
                ->whereKey($ids->all())
                ->delete();
        });

        Log::info('Deleted cancelled bookings older than '.self::RETENTION_DAYS.' days.', [
            'count' => $count,
            'booking_ids' => $ids->all(),
            'cutoff' => $cutoff->toDateTimeString(),
        ]);
    }
}