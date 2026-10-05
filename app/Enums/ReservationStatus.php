<?php

namespace App\Enums;

enum ReservationStatus: string
{
    case Tentative = 'tentative';
    case Confirmed = 'confirmed';
    case CheckedIn = 'checked_in';
    case CheckedOut = 'checked_out';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Tentative => __('Tentative'),
            self::Confirmed => __('Confirmed'),
            self::CheckedIn => __('Checked in'),
            self::CheckedOut => __('Checked out'),
            self::Cancelled => __('Cancelled'),
            self::NoShow => __('No show'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Tentative => 'secondary',
            self::Confirmed => 'primary',
            self::CheckedIn => 'success',
            self::CheckedOut => 'dark',
            self::Cancelled => 'danger',
            self::NoShow => 'warning',
        };
    }

    /** @return list<string> */
    public static function active(): array
    {
        return [self::Tentative->value, self::Confirmed->value, self::CheckedIn->value];
    }
}
