<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_otps', function (Blueprint $table) {
            $table->id();

            $table->string('full_name', 150);

            $table->string('country', 100);

            $table->string('nationality', 100)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('address')->nullable();

            $table->string('id_type', 50)->nullable();
            $table->string('id_number', 100)->nullable();

            $table->string('email', 255);
            $table->string('phone', 30);

            $table->string('password');

            $table->string('otp_hash');

            $table->timestamp('expires_at');

            $table->timestamp('verified_at')->nullable();

            $table->unsignedSmallInteger('attempts')->default(0);

            $table->timestamps();

            $table->index('email');
            $table->index('phone');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_otps');
    }
};
