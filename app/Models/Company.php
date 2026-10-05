<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = ['name', 'tax_number', 'contact_person', 'phone', 'email', 'credit_limit', 'is_active'];

    protected function casts(): array
    {
        return ['credit_limit' => 'decimal:3', 'is_active' => 'boolean'];
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(GuestAccount::class);
    }
}
