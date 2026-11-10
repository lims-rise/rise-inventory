<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HakAkses extends Model
{
    protected $table = 'tbl_hak_akses';
    public $timestamps = false;
    protected $fillable = ['id_user_level', 'id_menu'];
}
