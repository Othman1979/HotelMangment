<?php

namespace App\Models;

use App\Casts\DateOnly;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NightAudit extends Model
{
    protected $fillable = ['business_date', 'user_id', 'started_at', 'finished_at', 'rooms_total', 'rooms_occupied', 'rooms_out_of_order', 'room_revenue', 'other_revenue', 'tax_total', 'payments_total', 'no_shows', 'log'];

    protected function casts(): array
    {
        return [
            'business_date' => DateOnly::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'room_revenue' => 'decimal:3',
            'other_revenue' => 'decimal:3',
            'tax_total' => 'decimal:3',
            'payments_total' => 'decimal:3',
            'log' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function occupancyPercent(): float
    {
        $sellable = $this->rooms_total - $this->rooms_out_of_order;

        return $sellable > 0 ? round($this->rooms_occupied * 100 / $sellable, 1) : 0;
    }

    public function adr(): float
    {
        return $this->rooms_occupied > 0 ? round((float) $this->room_revenue / $this->rooms_occupied, 3) : 0;
    }

    public function revpar(): float
    {
        $sellable = $this->rooms_total - $this->rooms_out_of_order;

        return $sellable > 0 ? round((float) $this->room_revenue / $sellable, 3) : 0;
    }
}
