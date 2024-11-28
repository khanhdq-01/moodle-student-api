<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPasswordReset extends Model
{
    use HasFactory;

    protected $table = 'mdl_user_password_resets';
    protected $fillable = ['userid', 'token', 'timerequested', 'timererequested'];
    public $timestamps = false;
}
