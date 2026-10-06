<?php

namespace App\Models;

use App\Casts\DateOnly;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guest extends Model
{
    protected $fillable = ['full_name', 'gender', 'nationality', 'id_type', 'id_number', 'date_of_birth', 'phone', 'email', 'address', 'is_vip', 'is_blacklisted', 'notes'];

    protected function casts(): array
    {
        return ['date_of_birth' => DateOnly::class, 'is_vip' => 'boolean', 'is_blacklisted' => 'boolean'];
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
