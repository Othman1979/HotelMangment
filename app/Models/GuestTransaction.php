<?php

namespace App\Models;

use App\Casts\DateOnly;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GuestTransaction extends Model
{
    protected $fillable = ['guest_account_id', 'transaction_code_id', 'business_date', 'description', 'quantity', 'unit_price', 'amount', 'service_amount', 'tax_amount', 'outlet_check_id', 'payment_id', 'night_audit_id', 'shift_id', 'reversal_of_id', 'transferred_from_account_id', 'user_id', 'reason'];

    protected function casts(): array
    {
        return [
            'business_date' => DateOnly::class,
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:3',
            'amount' => 'decimal:3',
            'service_amount' => 'decimal:3',
            'tax_amount' => 'decimal:3',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(GuestAccount::class, 'guest_account_id');
    }

    public function code(): BelongsTo
    {
        return $this->belongsTo(TransactionCode::class, 'transaction_code_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function reversedBy(): HasOne
    {
        return $this->hasOne(self::class, 'reversal_of_id');
    }

    public function total(): float
    {
        return round((float) $this->amount + (float) $this->service_amount + (float) $this->tax_amount, 3);
    }
}
