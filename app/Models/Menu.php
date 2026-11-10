<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    protected $table = 'tbl_menu';
    protected $primaryKey = 'id_menu';
    public $timestamps = false;
    protected $fillable = ['title', 'url', 'icon', 'is_main_menu', 'is_aktif', '_index'];

    /** Sub menu: baris lain yang is_main_menu = id_menu menu ini. */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'is_main_menu', 'id_menu');
    }
}
