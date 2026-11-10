<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserReset extends Model
{
    protected $table = 'user_reset';
    protected $primaryKey = 'emails';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $fillable = ['emails', 'resetcode'];
}
