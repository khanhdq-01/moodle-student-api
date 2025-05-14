<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseModule extends Model
{
    use HasFactory;

    protected $table = 'mdl_course_modules'; // Tương ứng với bảng mdl_course_modules trong Moodle
    protected $fillable = [
        'course',
        'module',
        'instance',
        'section',
        'idnumber',
        'added',
        'score',
        'indent',
        'visible',
        'visibleoncoursepage',
        'visibleold',
        'groupmode',
        'groupingid',
        'completion',
        'completiongradeitemnumber',
        'completionview',
        'completionexpected',
        'showdescription',
        'availability',
        'deletioninprogress',
    ];
}
