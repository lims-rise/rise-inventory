<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefService extends Model
{
    protected $table = 'ref_services';
    protected $primaryKey = 'id_services';
    public $timestamps = false;
    protected $fillable = ['services', 'status'];
}
