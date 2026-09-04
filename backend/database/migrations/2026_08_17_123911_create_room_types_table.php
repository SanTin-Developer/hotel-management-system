<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();

            $table->string('name', 100)->unique();
            $table->text('description')->nullable();

            $table->unsignedSmallInteger('capacity');
            $table->decimal('base_price', 12, 2);

            $table->decimal('size', 8, 2)->nullable();
            $table->string('bed_type', 100)->nullable();

            $table->text('image_url')->nullable();

            $table->string('status', 30)->default('active');

            $table->timestamps();

            $table->index('status');
            $table->index('base_price');
            $table->index('capacity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_types');
    }
};
