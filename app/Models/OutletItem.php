<?php

namespace App\Models;

use App\Models\Concerns\HasBilingualName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutletItem extends Model
{
    use HasBilingualName;

    protected $fillable = ['outlet_id', 'category', 'name_ar', 'name_en', 'price', 'is_active'];

    protected function casts(): array
    {
        return ['price' => 'decimal:3', 'is_active' => 'boolean'];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }
}
