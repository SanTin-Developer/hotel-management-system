<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->cascadeOnDelete();

            $table->decimal('amount', 12, 2);

            $table->string('payment_method', 30);

            $table->string('transaction_id', 150)->nullable()->unique();

            $table->string('status', 30)->default('pending');

            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->index('booking_id');
            $table->index('status');
            $table->index('payment_method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
