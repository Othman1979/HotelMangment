<?php

namespace App\Models;

use App\Casts\DateOnly;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = ['invoice_no', 'guest_account_id', 'bill_to', 'tax_number', 'subtotal', 'service_amount', 'tax_amount', 'total', 'paid', 'business_date', 'user_id'];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:3',
            'service_amount' => 'decimal:3',
            'tax_amount' => 'decimal:3',
            'total' => 'decimal:3',
            'paid' => 'decimal:3',
            'business_date' => DateOnly::class,
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(GuestAccount::class, 'guest_account_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
