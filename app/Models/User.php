<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table = 'mdl_user';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username', 'firstname', 'lastname', 'email', 'password', 'phone1', 'city', 'country', 'lang'
    ];
    public static $validLanguages = ['en', 'vi', 'fr', 'de', 'es'];
    public $timestamps = false;
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    public static function authenticate($username, $password)
    {
        $user = self::where('username', $username)
            ->where('suspended', 0)
            ->where('auth', 'manual')
            ->first();

        if ($user && password_verify($password, $user->password)) {
            return $user;
        }

        return null;
    }

    public static function isValidLanguage(string $lang): bool
    {
        return in_array($lang, self::$validLanguages);
    }
}
