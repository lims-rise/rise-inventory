<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Tabel item_receipt tidak punya primary key (lihat catatan di ItemImage). */
class ItemReceipt extends Model
{
    protected $table = 'item_receipt';
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['inventory_number', 'image'];
}
