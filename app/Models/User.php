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
    protected $fillable = [
        'id',
        'auth',
        'policyagreed',
        'confirmed',
        'deleted',
        'mnethostid',
        'suspended',
        'username',
        'password',
        'idnumber',
        'firstname',
        'lastname',
        'middlename',
        'email',
        'emailstop',
        'icq',
        'skype',
        'aim',
        'phone1',
        'phone2',
        'institution',
        'department',
        'address',
        'city',
        'country',
        'lang',
        'calendartype',
        'theme',
        'timezone',
        'firstaccess',
        'lastaccess',
        'lastlogin',
        'currentlogin',
        'lastip',
        'secret',
        'picture',
        'url',
        'description',
        'descriptionformat',
        'mailformat',
        'maildigest',
        'maildisplay',
        'autosubscribe',
        'timecreated',
        'timemodified',
        'trustbitmask',
        'imagealt',
        'lastnamephonetic',
        'firstnamephonetic',
        'alternatename',
        'moodlenetprofile',
    ];


    public function courses()
    {
        return $this->belongsToMany(Course::class, 'mdl_role_assignments', 'userid', 'contextid')
            ->where('roleid', 5); // Role ID cho học viên
    }

    /**
     * Hàm lấy tên đầy đủ của người dùng
     * 
     * @return string
     */
    public function getFullnameAttribute()
    {
        return $this->firstname . ' ' . $this->lastname;
    }
}
