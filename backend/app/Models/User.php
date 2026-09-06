<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Employee/staff profile.
     */
    public function staff(): HasOne
    {
        return $this->hasOne(Staff::class);
    }

    /**
     * Guest profile associated with this user (matched by email).
     */
    public function guest(): HasOne
    {
        return $this->hasOne(Guest::class, 'email', 'email');
    }

    /**
     * Bookings created by this user.
     */
    public function createdBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'created_by');
    }

    /**
     * Booking status changes made by this user.
     */
    public function bookingStatusChanges(): HasMany
    {
        return $this->hasMany(
            BookingStatusHistory::class,
            'changed_by'
        );
    }

    /**
     * Room status changes made by this user.
     */
    public function roomStatusChanges(): HasMany
    {
        return $this->hasMany(
            RoomStatusHistory::class,
            'changed_by'
        );
    }
}
