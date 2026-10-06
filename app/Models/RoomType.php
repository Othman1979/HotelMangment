<?php

namespace App\Models;

use App\Models\Concerns\HasBilingualName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomType extends Model
{
    use HasBilingualName;

    protected $fillable = ['code', 'name_ar', 'name_en', 'max_adults', 'max_children', 'base_rate', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['base_rate' => 'decimal:3', 'is_active' => 'boolean'];
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }
}
