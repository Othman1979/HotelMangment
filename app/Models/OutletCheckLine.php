<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutletCheckLine extends Model
{
    public $timestamps = false;

    protected $fillable = ['outlet_check_id', 'outlet_item_id', 'name', 'quantity', 'unit_price', 'amount'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:2', 'unit_price' => 'decimal:3', 'amount' => 'decimal:3'];
    }
}
