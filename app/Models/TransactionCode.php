<?php

namespace App\Models;

use App\Enums\TransactionType;
use App\Models\Concerns\HasBilingualName;
use Illuminate\Database\Eloquent\Model;

class TransactionCode extends Model
{
    use HasBilingualName;

    public const ROOM = 'ROOM';

    public const NO_SHOW = 'NOSHOW';

    public const TRANSFER = 'TRANS';

    protected $fillable = ['code', 'name_ar', 'name_en', 'type', 'revenue_group', 'is_taxable', 'has_service', 'is_manual', 'is_active'];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'is_taxable' => 'boolean',
            'has_service' => 'boolean',
            'is_manual' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public static function byCode(string $code): self
    {
        return static::query()->where('code', $code)->firstOrFail();
    }
}
