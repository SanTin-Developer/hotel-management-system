<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->cascadeOnDelete();

            $table->foreignId('room_id')
                ->constrained('rooms')
                ->restrictOnDelete();

            $table->decimal('price_per_night', 12, 2);
            $table->unsignedSmallInteger('nights');
            $table->decimal('subtotal', 12, 2);

            $table->string('status', 30)->default('reserved');

            $table->timestamps();

            $table->index('booking_id');
            $table->index('room_id');

            $table->index(['room_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_items');
    }
};
