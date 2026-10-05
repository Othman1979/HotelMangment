<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiptVoucher extends Model
{
    protected $fillable = ['voucher_no', 'payment_id', 'type', 'received_from', 'print_count'];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
