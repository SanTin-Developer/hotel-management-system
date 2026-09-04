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

        DB::statement('
            CREATE OR REPLACE FUNCTION calculate_booking_nights(
                check_in_date DATE,
                check_out_date DATE
            )
            RETURNS INTEGER
            LANGUAGE SQL
            IMMUTABLE
            STRICT
            AS $$
                SELECT check_out_date - check_in_date;
            $$;
        ');

        DB::statement('
            CREATE OR REPLACE FUNCTION calculate_booking_subtotal(
                nightly_price NUMERIC,
                number_of_nights INTEGER
            )
            RETURNS NUMERIC(12,2)
            LANGUAGE SQL
            IMMUTABLE
            STRICT
            AS $$
                SELECT ROUND(nightly_price * number_of_nights, 2);
            $$;
        ');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(
            'DROP FUNCTION IF EXISTS calculate_booking_subtotal(NUMERIC, INTEGER)'
        );

        DB::statement(
            'DROP FUNCTION IF EXISTS calculate_booking_nights(DATE, DATE)'
        );
    }
};
