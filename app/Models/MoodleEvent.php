<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MoodleEvent extends Model
{

    // Tên bảng trong cơ sở dữ liệu Moodle
    protected $table = 'mdl_event'; // Tự động thêm prefix 'mdl_' nếu có trong cấu hình

    // Không có timestamps kiểu Laravel (created_at, updated_at)
    public $timestamps = false;

    // Các cột có thể được fill
    protected $fillable = [
        'name',
        'description',
        'format',
        'courseid',
        'groupid',
        'userid',
        'repeatid',
        'modulename',
        'instance',
        'eventtype',
        'timestart',
        'timeduration',
        'visible',
        'uuid',
        'sequence',
    ];

    // Bạn có thể thêm các accessor nếu cần định dạng timestamp:
    public function getTimestartFormattedAttribute()
    {
        return date('Y-m-d H:i:s', $this->timestart);
    }
}
