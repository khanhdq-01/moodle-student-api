<?php

namespace App\Repositories;

use App\Models\Course;
use Illuminate\Support\Facades\DB;
use App\Interfaces\CourseRepositoryInterface;

class CourseRepository implements CourseRepositoryInterface
{
    public function getAllCourses()
    {
        return DB::table('mdl_course as c')
            ->leftJoin('mdl_context as ctx', function ($join) {
                $join->on('ctx.instanceid', '=', 'c.id')
                    ->where('ctx.contextlevel', '=', 50); // Context level của khóa học
            })
            ->leftJoin('mdl_role_assignments as ra', 'ra.contextid', '=', 'ctx.id')
            ->leftJoin('mdl_user as u', function ($join) {
                $join->on('ra.userid', '=', 'u.id')
                    ->where('ra.roleid', '=', 3); // Giảng viên (roleid = 3)
            })
            ->leftJoin('mdl_user as a', function ($join) {
                $join->on('ra.userid', '=', 'a.id')
                    ->where('ra.roleid', '=', 4); // Trợ giảng (roleid = 4)
            })
            ->leftjoin('mdl_course_categories as cc', 'cc.id', '=', 'c.category')
            ->select(
                'c.id as course_id',
                'c.fullname as course_name',
                'c.summary as description',
                'cc.name as category_name',
                DB::raw('(SELECT COUNT(*) FROM mdl_course_modules cm WHERE cm.course = c.id) AS lesson_count'),
                DB::raw('(SELECT COUNT(*) FROM mdl_user_enrolments ue 
                          JOIN mdl_enrol e ON ue.enrolid = e.id WHERE e.courseid = c.id) AS student_count'),
                DB::raw('GROUP_CONCAT(DISTINCT CONCAT(u.firstname, " ", u.lastname, " (Giáo viên)") SEPARATOR ", ") AS teachers'),
                DB::raw('GROUP_CONCAT(DISTINCT CONCAT(a.firstname, " ", a.lastname, " (Trợ giảng)") SEPARATOR ", ") AS assistants')
            )
            ->groupBy('c.id', 'c.fullname', 'c.summary', 'cc.name')
            ->get();
    }

    public function getCoursesByStudentId($studentId)
    {
        return DB::table('mdl_course as c')
            ->leftJoin('mdl_context as ctx', function ($join) {
                $join->on('ctx.instanceid', '=', 'c.id')
                    ->where('ctx.contextlevel', '=', 50); // Context level của khóa học
            })
            ->leftJoin('mdl_role_assignments as ra', 'ra.contextid', '=', 'ctx.id')
            ->leftJoin('mdl_user as u', function ($join) {
                $join->on('ra.userid', '=', 'u.id')
                    ->where('ra.roleid', '=', 3); // Giảng viên (roleid = 3)
            })
            ->leftJoin('mdl_user as a', function ($join) {
                $join->on('ra.userid', '=', 'a.id')
                    ->where('ra.roleid', '=', 4); // Trợ giảng (roleid = 4)
            })
            ->leftJoin('mdl_course_categories as cc', 'cc.id', '=', 'c.category')
            ->join('mdl_user_enrolments as ue', 'ue.courseid', '=', 'c.id') // Join với bảng đăng ký sinh viên
            ->join('mdl_enrol as e', 'e.id', '=', 'ue.enrolid') // Join với bảng enrol để xác định course của sinh viên
            ->where('ue.userid', '=', $studentId) // Lọc theo ID của sinh viên
            ->select(
                'c.id as course_id',
                'c.fullname as course_name',
                'c.summary as description',
                'cc.name as category_name',
                DB::raw('(SELECT COUNT(*) FROM mdl_course_modules cm WHERE cm.course = c.id) AS lesson_count'),
                DB::raw('(SELECT COUNT(*) FROM mdl_user_enrolments ue 
                  JOIN mdl_enrol e ON ue.enrolid = e.id WHERE e.courseid = c.id) AS student_count'),
                DB::raw('GROUP_CONCAT(DISTINCT CONCAT(u.firstname, " ", u.lastname, " (Giáo viên)") SEPARATOR ", ") AS teachers'),
                DB::raw('GROUP_CONCAT(DISTINCT CONCAT(a.firstname, " ", a.lastname, " (Trợ giảng)") SEPARATOR ", ") AS assistants')
            )
            ->groupBy('c.id', 'c.fullname', 'c.summary', 'cc.name')
            ->get();
    }

    public function getAvailableCoursesForStudent($studentId)
    {
        // return Course::whereDoesntHave('students', function ($query) use ($studentId) {
        //     $query->where('userid', $studentId);
        // })
        //     ->with(['teachers', 'assistants', 'modules'])
        //     ->get();
    }
}
