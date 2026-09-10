<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_otps', function (Blueprint $table) {
            if (! Schema::hasColumn('registration_otps', 'nationality')) {
                $table->string('nationality', 100)->nullable()->after('country');
            }

            if (! Schema::hasColumn('registration_otps', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('nationality');
            }

            if (! Schema::hasColumn('registration_otps', 'address')) {
                $table->text('address')->nullable()->after('date_of_birth');
            }
        });
    }

    public function down(): void
    {
        Schema::table('registration_otps', function (Blueprint $table) {
            $table->dropColumn(['nationality', 'date_of_birth', 'address']);
        });
    }
};
