<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseCustomField extends Model
{
    use HasFactory;

    protected $table = 'mdl_customfield_data'; // Bảng chứa dữ liệu custom field của Moodle
    protected $fillable = [
        'id',
        'instanceid',
        'decvalue',
        'shortcharvalue',
        'charvalue',
        'value',
        'fieldid',
        'intvalue',
        'valueformat',
        'timemodified',
        'contextid',
        'intvalue',
    ];

    /**
     * Liên kết với bảng Course
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'instanceid', 'id');
    }
}
