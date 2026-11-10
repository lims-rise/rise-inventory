<?php

namespace App\Models;

use App\Models\Concerns\LegacyTimestamps;
use Illuminate\Database\Eloquent\Model;

class ItemManufacture extends Model
{
    use LegacyTimestamps;

    protected $table = 'item_manufacture';
    protected $fillable = ['name', 'status', 'created_by'];
}
