<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $table = 'location';
    public $timestamps = false; // tabel location tidak punya created_at
    protected $fillable = ['name', 'status'];

    public function details(): HasMany
    {
        return $this->hasMany(LocationDetail::class, 'id_location');
    }
}
