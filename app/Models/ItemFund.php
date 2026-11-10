<?php

namespace App\Models;

use App\Models\Concerns\LegacyTimestamps;
use Illuminate\Database\Eloquent\Model;

class ItemFund extends Model
{
    use LegacyTimestamps;

    protected $table = 'item_fund';
    protected $fillable = ['name', 'status', 'created_by'];
}
