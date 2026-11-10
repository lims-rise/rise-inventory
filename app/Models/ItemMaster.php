<?php

namespace App\Models;

use App\Models\Concerns\LegacyTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Data aset utama. Primary key = inventory_number (string, bukan auto increment).
 */
class ItemMaster extends Model
{
    use LegacyTimestamps;

    protected $table = 'item_master';
    protected $primaryKey = 'inventory_number';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'inventory_number', 'description', 'name', 'type', 'serial_number',
        'category_id', 'sub_category_id', 'created_by', 'status',
        'purchase_date', 'purchase_price', 'currency', 'fund_id',
        'manufacture_id', 'supplier_id', 'location_det_id', 'scheduled',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date:Y-m-d',
            'purchase_price' => 'float',
        ];
    }

    public const CURRENCIES = ['AUD', 'IDR', 'FJD', 'NZD', 'GBP'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(ItemSubCategory::class, 'sub_category_id');
    }

    public function fund(): BelongsTo
    {
        return $this->belongsTo(ItemFund::class, 'fund_id');
    }

    public function manufacture(): BelongsTo
    {
        return $this->belongsTo(ItemManufacture::class, 'manufacture_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(ItemSupplier::class, 'supplier_id');
    }

    public function locationDetail(): BelongsTo
    {
        return $this->belongsTo(LocationDetail::class, 'location_det_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ItemImage::class, 'inventory_number', 'inventory_number');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(ItemReceipt::class, 'inventory_number', 'inventory_number');
    }

    public function quantities(): HasMany
    {
        return $this->hasMany(ItemQty::class, 'inventory_number', 'inventory_number');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(ItemTransaction::class, 'inventory_number', 'inventory_number');
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(ItemMaintenance::class, 'inventory_number', 'inventory_number');
    }
}
