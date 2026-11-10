<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Tabel email_notif tidak punya primary key; update/hapus lewat where('email', ...). */
class EmailNotif extends Model
{
    protected $table = 'email_notif';
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['email', 'status', 'catlog'];
}
