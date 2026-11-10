<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserLevel extends Model
{
    protected $table = 'tbl_user_level';
    protected $primaryKey = 'id_user_level';
    public $timestamps = false;
    protected $fillable = ['nama_level', 'status', 'url'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'id_user_level', 'id_user_level');
    }

    /** Menu yang boleh diakses level ini (tbl_hak_akses). */
    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class, 'tbl_hak_akses', 'id_user_level', 'id_menu');
    }
}
