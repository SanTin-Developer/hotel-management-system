<?php

namespace App\Models;

use Database\Factories\GuestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guest extends Model
{
    /** @use HasFactory<GuestFactory> */
    use HasFactory;

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'address',
        'nationality',
        'id_type',
        'id_number',
        'gender',
        'date_of_birth',
        'country',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    /**
     * All booking made by this guest.
     * */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**!SECTION
     * All reviews written by this guest.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
