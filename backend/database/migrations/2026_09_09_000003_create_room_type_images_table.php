<?php

use App\Models\RoomType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_type_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')
                ->constrained('room_types')
                ->cascadeOnDelete();
            $table->text('image_url');
            $table->string('image_public_id')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['room_type_id', 'sort_order']);
        });

        $roomTypes = RoomType::query()
            ->select(['id', 'image_url', 'image_public_id'])
            ->whereNotNull('image_url')
            ->where('image_url', '!=', '')
            ->get();

        foreach ($roomTypes->chunk(500) as $chunk) {
            $now = now();

            DB::table('room_type_images')->insert(
                $chunk->map(fn (RoomType $roomType) => [
                    'room_type_id' => $roomType->id,
                    'image_url' => $roomType->image_url,
                    'image_public_id' => $roomType->image_public_id,
                    'sort_order' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all()
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('room_type_images');
    }
};
