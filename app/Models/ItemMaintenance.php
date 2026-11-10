<?php

namespace App\Models;

use App\Models\Concerns\LegacyTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemMaintenance extends Model
{
    use LegacyTimestamps;

    protected $table = 'item_maintenance';
    protected $primaryKey = 'id_maintenance';
    protected $fillable = [
        'inventory_number', 'last_service_date', 'id_services', 'provider_contact', 'id_staff',
        'service_desc', 'id_frequency', 'service_schedule', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return ['last_service_date' => 'date:Y-m-d'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(ItemMaster::class, 'inventory_number', 'inventory_number');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(RefService::class, 'id_services', 'id_services');
    }

    public function frequency(): BelongsTo
    {
        return $this->belongsTo(RefFrequency::class, 'id_frequency', 'id_frequency');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'id_staff');
    }
}
