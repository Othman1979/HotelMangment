<?php

namespace App\Enums;

enum TransactionType: string
{
    case Charge = 'charge';
    case Payment = 'payment';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Charge => __('Charge'),
            self::Payment => __('Payment'),
            self::Adjustment => __('Adjustment'),
        };
    }
}
