<?php

namespace App\Models;

use App\Casts\DateOnly;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    protected $fillable = ['payment_no', 'guest_account_id', 'payment_method_id', 'amount', 'reference', 'is_deposit', 'shift_id', 'business_date', 'user_id'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:3', 'is_deposit' => 'boolean', 'business_date' => DateOnly::class];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(GuestAccount::class, 'guest_account_id');
    }

    public function method(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function voucher(): HasOne
    {
        return $this->hasOne(ReceiptVoucher::class);
    }
}
