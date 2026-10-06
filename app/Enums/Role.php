<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case FrontDesk = 'front_desk';
    case Cashier = 'cashier';
    case NightAuditor = 'night_auditor';
    case Housekeeping = 'housekeeping';
    case Outlet = 'outlet';

    public function label(): string
    {
        return match ($this) {
            self::Admin => __('Administrator'),
            self::Manager => __('Manager'),
            self::FrontDesk => __('Front desk'),
            self::Cashier => __('Cashier'),
            self::NightAuditor => __('Night auditor'),
            self::Housekeeping => __('Housekeeping'),
            self::Outlet => __('Outlet staff'),
        };
    }
}
