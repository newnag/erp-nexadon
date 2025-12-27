<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    protected $fillable = [
        'ingredient_id',
        'type',
        'quantity',
        'unit_cost',
        'transaction_date',
        'notes',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
    ];

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}
