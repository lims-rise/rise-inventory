<?php

namespace App\Models;

use App\Models\Concerns\LegacyTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationDetail extends Model
{
    use LegacyTimestamps;

    protected $table = 'location_detail';
    protected $fillable = ['id_location', 'name', 'status', 'created_by'];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'id_location');
    }
}
