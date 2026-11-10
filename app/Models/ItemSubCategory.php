<?php

namespace App\Models;

use App\Models\Concerns\LegacyTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemSubCategory extends Model
{
    use LegacyTimestamps;

    protected $table = 'item_sub_category';
    protected $fillable = ['category_id', 'name', 'status', 'created_by'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }
}
