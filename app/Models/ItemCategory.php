<?php

namespace App\Models;

use App\Models\Concerns\LegacyTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemCategory extends Model
{
    use LegacyTimestamps;

    protected $table = 'item_category';
    protected $fillable = ['name', 'status', 'created_by'];

    public function subCategories(): HasMany
    {
        return $this->hasMany(ItemSubCategory::class, 'category_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ItemMaster::class, 'category_id');
    }
}
