<?php

namespace App\Enums;

enum HousekeepingStatus: string
{
    case Clean = 'clean';
    case Dirty = 'dirty';
    case Inspected = 'inspected';

    public function label(): string
    {
        return match ($this) {
            self::Clean => __('Clean'),
            self::Dirty => __('Dirty'),
            self::Inspected => __('Inspected'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Clean => 'success',
            self::Dirty => 'danger',
            self::Inspected => 'primary',
        };
    }
}
