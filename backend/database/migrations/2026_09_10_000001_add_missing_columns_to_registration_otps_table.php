<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_otps', function (Blueprint $table) {
            $table->string('nationality', 100)->nullable()->after('country');
            $table->date('date_of_birth')->nullable()->after('nationality');
            $table->text('address')->nullable()->after('date_of_birth');
        });
    }

    public function down(): void
    {
        Schema::table('registration_otps', function (Blueprint $table) {
            $table->dropColumn(['nationality', 'date_of_birth', 'address']);
        });
    }
};
