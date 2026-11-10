<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefFrequency extends Model
{
    protected $table = 'ref_frequency';
    protected $primaryKey = 'id_frequency';
    public $timestamps = false;
    protected $fillable = ['frequency', 'months', 'status'];
}
