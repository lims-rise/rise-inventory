<?php

namespace App\Models;

use App\Models\Concerns\LegacyTimestamps;
use Illuminate\Database\Eloquent\Model;

class ItemCondition extends Model
{
    use LegacyTimestamps;

    protected $table = 'item_condition';
    protected $fillable = ['name', 'status', 'created_by'];
}
