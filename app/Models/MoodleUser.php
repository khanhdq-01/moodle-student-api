<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class MoodleUser extends Authenticatable
{
    use HasApiTokens;

    use Notifiable;

    public $timestamps = false;

    protected $table = 'mdl_user';
    protected $primaryKey = 'id';

    protected $fillable = [
        'username',
        'password',
        'fullname',
        'email',
        'phone',
        'status',
        'created_at',
        'updated_at',
        'role',
        'type',
        // Thêm các trường khác nếu cần
    ];

    protected $hidden = [
        'password', // Ẩn mật khẩu trong các response
    ];
}
