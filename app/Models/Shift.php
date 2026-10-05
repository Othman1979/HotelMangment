<?php

namespace App\Models;

use App\Casts\DateOnly;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    protected $fillable = ['shift_no', 'user_id', 'business_date', 'opened_at', 'opening_balance', 'closed_at', 'expected_cash', 'counted_cash', 'difference', 'status', 'notes'];

    protected function casts(): array
    {
        return [
            'business_date' => DateOnly::class,
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_balance' => 'decimal:3',
            'expected_cash' => 'decimal:3',
            'counted_cash' => 'decimal:3',
            'difference' => 'decimal:3',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function cashCollected(): string
    {
        return (string) $this->payments()
            ->whereHas('method', fn ($q) => $q->where('is_cash', true))
            ->sum('amount');
    }
}
