<?php

namespace App\Models;

use App\Models\Concerns\LegacyTimestamps;
use Illuminate\Database\Eloquent\Model;

class ItemType extends Model
{
    use LegacyTimestamps;

    protected $table = 'item_type';
    protected $fillable = ['name', 'status', 'created_by'];
}
