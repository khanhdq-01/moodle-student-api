<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseCategory extends Model
{
    use HasFactory;
    protected $table = "mdl_course_categories";
    protected $fillable =
    [
        'id',
        'name',
        'idnumber',
        'description',
        'descriptionformat',
        'parent',
        'sortorder',
        'coursecount',
        'visible',
        'visibleold',
        'timemodified',
        'depth',
        'path',
        'theme',
    ];
}
