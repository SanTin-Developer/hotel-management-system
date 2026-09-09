<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrationOtp extends Model
{
    protected $fillable = [
        'full_name',
        'country',
        'nationality',
        'date_of_birth',
        'address',
        'id_type',
        'id_number',
        'email',
        'phone',
        'password',
        'otp_hash',
        'expires_at',
        'verified_at',
        'attempts',
        'photo_url',
        'photo_public_id',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'date_of_birth' => 'date',
    ];
}
