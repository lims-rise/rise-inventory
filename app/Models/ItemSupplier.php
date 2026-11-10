<?php

namespace App\Models;

use App\Models\Concerns\LegacyTimestamps;
use Illuminate\Database\Eloquent\Model;

class ItemSupplier extends Model
{
    use LegacyTimestamps;

    protected $table = 'item_supplier';
    protected $fillable = ['name', 'status', 'created_by'];
}
