<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Enums\ServiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomBlock extends Model
{
    protected $fillable = ['room_id', 'from_date', 'to_date', 'type', 'reason', 'user_id', 'released_at'];

    protected function casts(): array
    {
        return [
            'from_date' => DateOnly::class,
            'to_date' => DateOnly::class,
            'type' => ServiceStatus::class,
            'released_at' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
