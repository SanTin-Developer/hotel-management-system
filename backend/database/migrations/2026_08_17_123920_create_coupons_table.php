<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();

            $table->string('code', 50)->unique();

            $table->string('discount_type', 20);
            $table->decimal('discount_value', 12, 2);

            $table->decimal('min_amount', 12, 2)->default(0);

            $table->timestamp('start_date');
            $table->timestamp('end_date');

            $table->unsignedInteger('usage_limit')->nullable();

            $table->string('status', 30)->default('active');

            $table->timestamps();

            $table->index('status');
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
