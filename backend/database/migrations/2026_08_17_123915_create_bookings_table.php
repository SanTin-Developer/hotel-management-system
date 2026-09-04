<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            $table->string('booking_code', 50)->unique();

            $table->foreignId('guest_id')
                ->constrained('guests')
                ->restrictOnDelete();

            $table->date('check_in');
            $table->date('check_out');

            $table->unsignedSmallInteger('adults')->default(1);
            $table->unsignedSmallInteger('children')->default(0);

            $table->decimal('total_amount', 12, 2)->default(0);

            $table->string('booking_source', 30)->default('website');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('status', 30)->default('pending');

            $table->text('special_request')->nullable();

            $table->timestamps();

            $table->index('guest_id');
            $table->index('status');
            $table->index('check_in');
            $table->index('check_out');

            $table->index([
                'status',
                'check_in',
                'check_out',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
