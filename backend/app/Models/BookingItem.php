<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'room_id',
        'price_per_night',
        'nights',
        'subtotal',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price_per_night' => 'decimal:2',
            'nights' => 'integer',
            'subtotal' => 'decimal:2',
        ];
    }

    /**
     * The booking this line item belongs to.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * The room reserved in this line item.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
