<?php

namespace App\Models;

use App\Models\Concerns\LegacyTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use LegacyTimestamps;

    protected $table = 'department';
    protected $fillable = ['name', 'status', 'created_by'];

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class, 'department_id');
    }
}
