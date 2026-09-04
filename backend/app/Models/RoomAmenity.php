<?php

namespace App\Models;

use Database\Factories\RoomAmenityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pivot model for the room_amenities table (composite PK: room_id, amenity_id).
 * Usually accessed implicitly through Room::amenities() / Amenity::rooms(),
 * but exposed here in case direct queries on the pivot are needed.
 */
class RoomAmenity extends Model
{
    /** @use HasFactory<RoomAmenityFactory> */
    use HasFactory;

    protected $table = 'room_amenities';

    public $incrementing = false; // composite PK, not auto-incrementing

    protected $primaryKey = null; // composite PK

    public $timestamps = false; // pivot table, no timestamps

    protected $fillable = [
        'room_id',
        'amenity_id',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function amenity(): BelongsTo
    {
        return $this->belongsTo(Amenity::class);
    }
}
