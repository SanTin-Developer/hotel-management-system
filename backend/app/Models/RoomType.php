<?php

namespace App\Models;

use Database\Factories\RoomTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomType extends Model
{
    /** @use HasFactory<RoomTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'capacity',
        'base_price',
        'size',
        'bed_type',
        'image_url',
        'image_public_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'base_price' => 'decimal:2',
            'size' => 'decimal:2',
        ];
    }

    /**!SECTION
     * All pysical rooms that belong to this room type.
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }
}
