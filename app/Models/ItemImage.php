<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tabel item_image tidak punya primary key.
 * Baca & insert lewat model ini; hapus lewat query builder:
 *   ItemImage::where('inventory_number', $no)->where('image', $file)->delete();
 */
class ItemImage extends Model
{
    protected $table = 'item_image';
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['inventory_number', 'image'];
}
