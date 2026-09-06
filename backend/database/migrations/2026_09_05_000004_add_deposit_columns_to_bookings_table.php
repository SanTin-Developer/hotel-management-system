<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('deposit_rate', 5, 2)->nullable()->after('total_amount');
            $table->decimal('deposit_amount', 12, 2)->default(0)->after('deposit_rate');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['deposit_rate', 'deposit_amount']);
        });
    }
};