<?php

namespace App\Enums;

enum OccupancyStatus: string
{
    case Vacant = 'vacant';
    case Occupied = 'occupied';

    public function label(): string
    {
        return match ($this) {
            self::Vacant => __('Vacant'),
            self::Occupied => __('Occupied'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Vacant => 'secondary',
            self::Occupied => 'success',
        };
    }
}
