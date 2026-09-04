<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->id();

            $table->string('full_name', 150);
            $table->string('email', 150)->nullable()->index();
            $table->string('phone', 30)->nullable()->index();

            $table->text('address')->nullable();
            $table->string('nationality', 100)->nullable();

            $table->string('id_type', 50)->nullable();
            $table->string('id_number', 100)->nullable();

            $table->string('gender', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('country', 100)->nullable();

            $table->timestamps();

            $table->index('full_name');
            $table->index(['id_type', 'id_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
