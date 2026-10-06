<?php

namespace App\Models;

use App\Models\Concerns\HasBilingualName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentMethod extends Model
{
    use HasBilingualName;

    protected $fillable = ['code', 'name_ar', 'name_en', 'transaction_code_id', 'is_cash', 'requires_reference', 'is_active'];

    protected function casts(): array
    {
        return ['is_cash' => 'boolean', 'requires_reference' => 'boolean', 'is_active' => 'boolean'];
    }

    public function transactionCode(): BelongsTo
    {
        return $this->belongsTo(TransactionCode::class);
    }
}
