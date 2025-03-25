<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RoleAssignment extends Model
{
    use HasFactory;
    protected $table = ['mdl_role_assignments'];
    protected $fillable = [
        'id',
        'roleid',
        'contextid',
        'userid',
        'timemodified',
        'modifieridid',
        'component',
        'itemid',
        'sortorder',
    ];


    // Quan hệ với bảng User
    public function user()
    {
        return $this->belongsTo(User::class, 'userid');
    }

    // Quan hệ với bảng Course
    public function course()
    {
        return $this->belongsTo(Course::class, 'contextid', 'id');
    }
}
