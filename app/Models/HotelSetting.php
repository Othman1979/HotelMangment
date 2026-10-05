<?php

namespace App\Models;

use App\Casts\DateOnly;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

class HotelSetting extends Model
{
    protected $fillable = ['name_ar', 'name_en', 'tax_number', 'address', 'phone', 'currency', 'business_date', 'check_in_time', 'check_out_time', 'tax_percent', 'service_percent'];

    protected function casts(): array
    {
        return [
            'business_date' => DateOnly::class,
            'tax_percent' => 'decimal:2',
            'service_percent' => 'decimal:2',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrFail();
    }

    public static function businessDate(): CarbonImmutable
    {
        return static::current()->business_date;
    }

    public function name(): string
    {
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }
}
