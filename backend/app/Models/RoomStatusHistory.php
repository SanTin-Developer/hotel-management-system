<?php

namespace App\Models;

use Database\Factories\RoomStatusHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomStatusHistory extends Model
{
    /** @use HasFactory<RoomStatusHistoryFactory> */
    use HasFactory;

    const UPDATED_AT = null; // Disable updated_at timestamp

    protected $fillable = [
        'room_id',
        'status',
        'changed_by',
        'note',
    ];

    /**!SECTION
     * The room this status entry belongs to.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**!SECTION
     * The user (staff) who made this status change. This is a foreign key to the users table, not the staff table, since staff are users.
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
