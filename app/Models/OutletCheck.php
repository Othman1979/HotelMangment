<?php

namespace App\Models;

use App\Casts\DateOnly;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutletCheck extends Model
{
    protected $fillable = ['check_no', 'outlet_id', 'settlement', 'guest_account_id', 'payment_method_id', 'customer_name', 'subtotal', 'service_amount', 'tax_amount', 'total', 'business_date', 'shift_id', 'user_id'];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:3',
            'service_amount' => 'decimal:3',
            'tax_amount' => 'decimal:3',
            'total' => 'decimal:3',
            'business_date' => DateOnly::class,
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(GuestAccount::class, 'guest_account_id');
    }

    public function method(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OutletCheckLine::class);
    }
}
