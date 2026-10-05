<?php

namespace App\Models;

use App\Enums\AccountType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuestAccount extends Model
{
    protected $fillable = ['account_no', 'type', 'reservation_room_id', 'company_id', 'guest_id', 'name', 'status', 'balance', 'allow_posting', 'closed_at'];

    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'balance' => 'decimal:3',
            'allow_posting' => 'boolean',
            'closed_at' => 'datetime',
        ];
    }

    public function stay(): BelongsTo
    {
        return $this->belongsTo(ReservationRoom::class, 'reservation_room_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(GuestTransaction::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
