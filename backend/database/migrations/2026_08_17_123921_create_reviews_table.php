<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->cascadeOnDelete();

            $table->foreignId('guest_id')
                ->constrained('guests')
                ->cascadeOnDelete();

            $table->unsignedSmallInteger('rating');

            $table->text('comment')->nullable();

            $table->string('status', 30)->default('pending');

            $table->timestamps();

            $table->index('booking_id');
            $table->index('guest_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
