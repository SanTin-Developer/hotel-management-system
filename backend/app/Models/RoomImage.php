<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'image_url',
        'image_public_id',
        'sort_order',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
