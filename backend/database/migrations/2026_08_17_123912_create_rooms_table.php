<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();

            $table->foreignId('room_type_id')
                ->constrained('room_types')
                ->restrictOnDelete();

            $table->string('room_number', 20)->unique();
            $table->unsignedSmallInteger('floor');

            $table->string('status', 30)->default('available');

            $table->text('description')->nullable();

            $table->timestamps();

            $table->index('room_type_id');
            $table->index('status');
            $table->index(['room_type_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
