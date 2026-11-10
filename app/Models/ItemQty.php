<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemQty extends Model
{
    protected $table = 'item_qty';
    public $timestamps = false;
    protected $fillable = ['inventory_number', 'qty', 'comments'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(ItemMaster::class, 'inventory_number', 'inventory_number');
    }
}
