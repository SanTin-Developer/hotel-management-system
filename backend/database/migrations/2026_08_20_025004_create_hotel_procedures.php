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
            CREATE OR REPLACE PROCEDURE record_booking_status_change(
                p_booking_id BIGINT,
                p_status VARCHAR(30),
                p_changed_by BIGINT DEFAULT NULL,
                p_note TEXT DEFAULT NULL
            )
            LANGUAGE plpgsql
            AS $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1
                    FROM bookings
                    WHERE id = p_booking_id
                ) THEN
                    RAISE EXCEPTION 'Booking % does not exist', p_booking_id;
                END IF;

                UPDATE bookings
                SET
                    status = p_status,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = p_booking_id;
            END;
            $$;
        SQL);
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(
            'DROP PROCEDURE IF EXISTS record_booking_status_change(BIGINT, VARCHAR, BIGINT, TEXT)'
        );
    }
};
