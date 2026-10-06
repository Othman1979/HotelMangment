<?php

namespace App\Models;

use App\Enums\HousekeepingStatus;
use App\Enums\OccupancyStatus;
use App\Enums\ServiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $fillable = ['room_number', 'room_type_id', 'floor', 'housekeeping_status', 'occupancy_status', 'service_status', 'notes', 'is_active'];

    protected function casts(): array
    {
        return [
            'housekeeping_status' => HousekeepingStatus::class,
            'occupancy_status' => OccupancyStatus::class,
            'service_status' => ServiceStatus::class,
            'is_active' => 'boolean',
        ];
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(RoomBlock::class);
    }

    public function stays(): HasMany
    {
        return $this->hasMany(ReservationRoom::class);
    }

    public function isReadyForCheckIn(): bool
    {
        return $this->occupancy_status === OccupancyStatus::Vacant
            && $this->service_status === ServiceStatus::InService
            && $this->housekeeping_status !== HousekeepingStatus::Dirty;
    }

    public function statusCode(): string
    {
        if ($this->service_status !== ServiceStatus::InService) {
            return $this->service_status === ServiceStatus::OutOfOrder ? 'OOO' : 'OOS';
        }

        return ($this->occupancy_status === OccupancyStatus::Occupied ? 'O' : 'V')
            .match ($this->housekeeping_status) {
                HousekeepingStatus::Clean => 'C',
                HousekeepingStatus::Dirty => 'D',
                HousekeepingStatus::Inspected => 'I',
            };
    }
}
