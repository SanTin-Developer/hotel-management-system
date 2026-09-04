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
            CREATE OR REPLACE FUNCTION trg_record_booking_status_change()
            RETURNS TRIGGER
            LANGUAGE plpgsql
            AS $$
            BEGIN
                IF TG_OP = 'INSERT' THEN
                    INSERT INTO booking_status_histories (
                        booking_id,
                        status,
                        changed_by,
                        note,
                        created_at
                    )
                    VALUES (
                        NEW.id,
                        NEW.status,
                        NEW.created_by,
                        'Initial booking status',
                        CURRENT_TIMESTAMP
                    );

                    RETURN NEW;
                END IF;

                IF TG_OP = 'UPDATE'
                   AND OLD.status IS DISTINCT FROM NEW.status THEN

                    INSERT INTO booking_status_histories (
                        booking_id,
                        status,
                        changed_by,
                        note,
                        created_at
                    )
                    VALUES (
                        NEW.id,
                        NEW.status,
                        NEW.created_by,
                        'Booking status changed',
                        CURRENT_TIMESTAMP
                    );
                END IF;

                RETURN NEW;
            END;
            $$;
        SQL);

        DB::statement(<<<'SQL'
            CREATE TRIGGER trg_bookings_status_history
            AFTER INSERT OR UPDATE OF status
            ON bookings
            FOR EACH ROW
            EXECUTE FUNCTION trg_record_booking_status_change();
        SQL);

        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION trg_record_room_status_change()
            RETURNS TRIGGER
            LANGUAGE plpgsql
            AS $$
            BEGIN
                IF TG_OP = 'INSERT' THEN
                    INSERT INTO room_status_histories (
                        room_id,
                        status,
                        changed_by,
                        note,
                        created_at
                    )
                    VALUES (
                        NEW.id,
                        NEW.status,
                        NULL,
                        'Initial room status',
                        CURRENT_TIMESTAMP
                    );

                    RETURN NEW;
                END IF;

                IF TG_OP = 'UPDATE'
                AND OLD.status IS DISTINCT FROM NEW.status THEN

                    INSERT INTO room_status_histories (
                        room_id,
                        status,
                        changed_by,
                        note,
                        created_at
                    )
                    VALUES (
                        NEW.id,
                        NEW.status,
                        NULL,
                        'Room status changed',
                        CURRENT_TIMESTAMP
                    );
                END IF;

                RETURN NEW;
            END;
            $$;
        SQL);

        DB::statement(<<<'SQL'
            CREATE TRIGGER trg_rooms_status_history
            AFTER INSERT OR UPDATE OF status
            ON rooms
            FOR EACH ROW
            EXECUTE FUNCTION trg_record_room_status_change();
        SQL);
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(
            'DROP TRIGGER IF EXISTS trg_bookings_status_history ON bookings'
        );

        DB::statement(
            'DROP TRIGGER IF EXISTS trg_rooms_status_history ON rooms'
        );

        DB::statement(
            'DROP FUNCTION IF EXISTS trg_record_booking_status_change()'
        );

        DB::statement(
            'DROP FUNCTION IF EXISTS trg_record_room_status_change()'
        );
    }
};
