<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Enums\AccountType;
use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ReservationRoom extends Model
{
    protected $fillable = ['reservation_id', 'room_type_id', 'room_id', 'arrival_date', 'departure_date', 'adults', 'children', 'nightly_rate', 'status', 'checked_in_at', 'checked_out_at'];

    protected function casts(): array
    {
        return [
            'arrival_date' => DateOnly::class,
            'departure_date' => DateOnly::class,
            'nightly_rate' => 'decimal:3',
            'status' => ReservationStatus::class,
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function account(): HasOne
    {
        return $this->hasOne(GuestAccount::class)->where('type', AccountType::Guest->value)->latestOfMany();
    }

    public function nights(): int
    {
        return (int) $this->arrival_date->diffInDays($this->departure_date);
    }
}
