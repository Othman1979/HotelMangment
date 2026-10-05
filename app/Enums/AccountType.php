<?php

namespace App\Enums;

enum AccountType: string
{
    case Guest = 'guest';
    case CityLedger = 'city_ledger';
    case NonGuest = 'non_guest';

    public function label(): string
    {
        return match ($this) {
            self::Guest => __('Guest account'),
            self::CityLedger => __('City ledger'),
            self::NonGuest => __('Non-guest account'),
        };
    }
}
