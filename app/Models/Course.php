<?php

namespace App\Models;

use App\Models\User;
use App\Models\CourseModule;
use App\Models\CourseCustomField;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Course extends Model
{
    use HasFactory;

    protected $table = 'mdl_course'; // Bảng Moodle mặc định
    protected $fillable = [
        'id',
        'category',
        'fullname',
        'shortname',
        'sortorder',
        'idnumber',
        'summary',
        'summaryformat',
        'format',
        'showgrades',
        'newsitems',
        'startdate',
        'enddate',
        'relativedatesmode',
        'marker',
        'maxbytes',
        'legacyfiles',
        'showreports',
        'visible',
        'visibleold',
        'groupmode',
        'groupmodeforce',
        'defaultgroupingid',
        'lang',
        'calendartype',
        'theme',
        'timecreated',
        'timemodified',
        'requested',
        'enablecompletion',
        'completionnotify',
        'cacherev',


    ];

    /**
     * Quan hệ với giảng viên (roleid = 4 trong mdl_role_assignments)
     */
    // public function teachers()
    // 
    //     return $this->belongsToMany(User::class, 'mdl_role_assignments', 'contextid', 'userid')
    //         ->where('roleid', 3)  // Giảng viên có roleid = 3
    //         ->join('mdl_context', 'mdl_context.id', '=', 'mdl_role_assignments.contextid')
    //         ->where('mdl_context.instanceid', '=', $this->id) // Kết nối với khóa học hiện tại
    //         ->where('mdl_context.contextlevel', '=', 10); // Contextlevel = 10 cho khóa học
    // }
    /**
     * Quan hệ với trợ giảng (roleid = 3 trong mdl_role_assignments)
     */
    public function assistants()
    {
        return $this->belongsToMany(User::class, 'mdl_role_assignments', 'contextid', 'userid')
            ->where('roleid', 4); // Role ID cho trợ giảng
    }


    /**
     * Quan hệ với học viên (roleid = 5 trong mdl_role_assignments)
     */
    public function students()
    {
        return $this->belongsToMany(User::class, 'mdl_role_assignments', 'contextid', 'userid')
            ->where('roleid', 5); // Role ID cho học viên
    }


    /**
     * Quan hệ với các module trong khóa học
     */
    public function modules()
    {
        return $this->hasMany(CourseModule::class, 'course', 'id');
    }

    /**
     * Trường custom fields của khóa học
     */
    public function customFields()
    {
        return $this->hasMany(CourseCustomField::class, 'instanceid', 'id');
    }
}
