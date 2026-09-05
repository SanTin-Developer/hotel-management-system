<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_otps', function (Blueprint $table) {
            $table->text('photo_url')->nullable()->after('attempts');
            $table->string('photo_public_id')->nullable()->after('photo_url');
        });
    }

    public function down(): void
    {
        Schema::table('registration_otps', function (Blueprint $table) {
            $table->dropColumn(['photo_url', 'photo_public_id']);
        });
    }
};