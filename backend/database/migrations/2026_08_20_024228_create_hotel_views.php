<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(<<<'SQL'
            CREATE VIEW v_room_status_summary AS
            SELECT
                status,
                COUNT(*) AS total_rooms
            FROM rooms
            GROUP BY status
            ORDER BY status
        SQL);

        DB::statement(<<<'SQL'
            CREATE VIEW v_booking_summary AS
            SELECT
                status,
                COUNT(*) AS total_bookings,
                COALESCE(SUM(total_amount), 0) AS total_amount
            FROM bookings
            GROUP BY status
            ORDER BY status
        SQL);

        DB::statement(<<<'SQL'
            CREATE VIEW v_revenue_summary AS
            SELECT
                DATE(paid_at) AS revenue_date,
                COALESCE(SUM(amount), 0) AS total_revenue
            FROM payments
            WHERE status = 'paid'
              AND paid_at IS NOT NULL
            GROUP BY DATE(paid_at)
            ORDER BY revenue_date
        SQL);

        DB::statement(<<<'SQL'
            CREATE VIEW v_occupancy_summary AS
            SELECT
                COUNT(*) AS total_rooms,
                COUNT(*) FILTER (
                    WHERE status = 'occupied'
                ) AS occupied_rooms,
                COUNT(*) FILTER (
                    WHERE status = 'available'
                ) AS available_rooms
            FROM rooms
        SQL);
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP VIEW IF EXISTS v_occupancy_summary');
        DB::statement('DROP VIEW IF EXISTS v_revenue_summary');
        DB::statement('DROP VIEW IF EXISTS v_booking_summary');
        DB::statement('DROP VIEW IF EXISTS v_room_status_summary');
    }
};
