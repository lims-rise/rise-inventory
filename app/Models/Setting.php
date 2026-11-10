<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'tbl_setting';
    protected $primaryKey = 'id_setting';
    public $timestamps = false;
    protected $fillable = ['nama_setting', 'value'];
}
