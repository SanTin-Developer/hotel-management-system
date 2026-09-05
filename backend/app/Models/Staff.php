<?php

namespace App\Models;

use Database\Factories\StaffFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Staff extends Model
{
    /** @use HasFactory<StaffFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_id',
        'position',
        'hire_date',
        'photo_url',
        'photo_public_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
        ];
    }

    /**!SECTION
     * The user account linked to this staff profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
