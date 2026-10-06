<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditTrail extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'action', 'entity_type', 'entity_id', 'old_values', 'new_values', 'ip'];

    protected function casts(): array
    {
        return ['old_values' => 'array', 'new_values' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $action, ?Model $entity = null, ?array $old = null, ?array $new = null): void
    {
        static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => $entity ? class_basename($entity) : null,
            'entity_id' => $entity?->getKey(),
            'old_values' => $old,
            'new_values' => $new,
            'ip' => request()?->ip(),
        ]);
    }
}
