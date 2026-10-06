<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Sequence extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'name';

    protected $keyType = 'string';

    protected $fillable = ['name', 'prefix', 'next_value'];

    public const PREFIXES = [
        'reservation' => 'R',
        'account' => 'A',
        'payment' => 'P',
        'receipt' => 'RV',
        'refund' => 'PV',
        'invoice' => 'INV',
        'check' => 'CHK',
        'shift' => 'SH',
    ];

    /** Gap-free numbering; must run inside the caller's DB transaction. */
    public static function next(string $name): string
    {
        return DB::transaction(function () use ($name) {
            $seq = static::query()->lockForUpdate()->find($name)
                ?? static::create(['name' => $name, 'prefix' => self::PREFIXES[$name] ?? strtoupper($name), 'next_value' => 1]);
            $value = $seq->next_value;
            $seq->update(['next_value' => $value + 1]);

            return $seq->prefix.'-'.str_pad((string) $value, 6, '0', STR_PAD_LEFT);
        });
    }
}
