<?php

namespace App\Enums;

enum ServiceStatus: string
{
    case InService = 'in_service';
    case OutOfOrder = 'out_of_order';
    case OutOfService = 'out_of_service';

    public function label(): string
    {
        return match ($this) {
            self::InService => __('In service'),
            self::OutOfOrder => __('Out of order'),
            self::OutOfService => __('Out of service'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::InService => 'success',
            self::OutOfOrder => 'danger',
            self::OutOfService => 'warning',
        };
    }
}
