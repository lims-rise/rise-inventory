<?php

namespace App\Models;

use App\Models\Concerns\LegacyTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemTransaction extends Model
{
    use LegacyTimestamps;

    protected $table = 'item_transaction';
    protected $fillable = [
        'inventory_number', 'location_id', 'department_id', 'staff_id', 'purpose',
        'condition_id', 'remark', 'status', 'transaction_at', 'transaction_by', 'created_by',
    ];

    protected function casts(): array
    {
        return ['transaction_at' => 'datetime'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(ItemMaster::class, 'inventory_number', 'inventory_number');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function condition(): BelongsTo
    {
        return $this->belongsTo(ItemCondition::class, 'condition_id');
    }
}
