<?php

namespace App\Models;

use App\Models\Concerns\HasBilingualName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Outlet extends Model
{
    use HasBilingualName;

    protected $fillable = ['code', 'name_ar', 'name_en', 'transaction_code_id', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function transactionCode(): BelongsTo
    {
        return $this->belongsTo(TransactionCode::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OutletItem::class);
    }

    public function checks(): HasMany
    {
        return $this->hasMany(OutletCheck::class);
    }
}
