<?php

use App\Models\Room;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')
                ->constrained('rooms')
                ->cascadeOnDelete();
            $table->text('image_url');
            $table->string('image_public_id')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['room_id', 'sort_order']);
        });

        $rooms = Room::query()
            ->select(['id', 'image_url', 'image_public_id'])
            ->whereNotNull('image_url')
            ->where('image_url', '!=', '')
            ->get();

        foreach ($rooms->chunk(500) as $chunk) {
            $now = now();

            DB::table('room_images')->insert(
                $chunk->map(fn (Room $room) => [
                    'room_id' => $room->id,
                    'image_url' => $room->image_url,
                    'image_public_id' => $room->image_public_id,
                    'sort_order' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all()
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('room_images');
    }
};
