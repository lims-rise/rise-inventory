<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemNotif extends Model
{
    protected $table = 'item_notif';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $fillable = ['id', 'years', 'updated'];
}
