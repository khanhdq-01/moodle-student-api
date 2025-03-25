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

    public function getDetailCoursesForStudent($courseId, $studentId)
    {

        return DB::table('mdl_course as c')
            ->join('mdl_course_sections as cs', 'cs.course', '=', 'c.id')
            ->join('mdl_course_modules as cm', 'cm.section', '=', 'cs.id')
            ->join('mdl_modules as m', 'm.id', '=', 'cm.module')
            ->leftJoin('mdl_user_enrolments as ue', 'ue.userid', '=', 'ue.userid')
            ->leftJoin('mdl_enrol as e', function ($join) use ($studentId) {
                $join->on('e.id', '=', 'ue.enrolid')
                    ->on('e.courseid', '=', 'c.id');
            })
            ->leftJoin('mdl_resource as r', function ($join) {
                $join->on('r.id', '=', 'cm.instance')
                    ->where('m.name', '=', 'resource'); // Tài liệu
            })
            ->leftJoin('mdl_page as p', function ($join) {
                $join->on('p.id', '=', 'cm.instance')
                    ->where('m.name', '=', 'page'); // Trang bài giảng
            })
            ->leftJoin('mdl_assign as a', function ($join) {
                $join->on('a.id', '=', 'cm.instance')
                    ->where('m.name', '=', 'assign'); // Bài tập
            })
            ->leftJoin('mdl_quiz as q', function ($join) {
                $join->on('q.id', '=', 'cm.instance')
                    ->where('m.name', '=', 'quiz'); // Bài trắc nghiệm
            })
            ->leftJoin('mdl_bigbluebuttonbn as bbb', function ($join) {
                $join->on('bbb.id', '=', 'cm.instance')
                    ->where('m.name', '=', 'bigbluebuttonbn'); // Buổi học online
            })
            ->select(
                'c.id as course_id',
                'c.fullname as course_name',
                'cs.name as section_name',
                'cs.sequence as sequence',
                'm.name as module_type',
                DB::raw('CASE 
                        WHEN m.name = "resource" THEN r.name
                        WHEN m.name = "page" THEN p.name
                        WHEN m.name = "assign" THEN a.name
                        WHEN m.name = "quiz" THEN q.name
                        WHEN m.name = "bigbluebuttonbn" THEN bbb.name
                     END as activity_name'),
                DB::raw('CASE 
                        WHEN m.name = "resource" THEN r.intro
                        WHEN m.name = "page" THEN p.content
                        WHEN m.name = "assign" THEN a.intro
                        WHEN m.name = "quiz" THEN q.intro
                        WHEN m.name = "bigbluebuttonbn" THEN bbb.intro
                     END as activity_details')
            )
            ->where('c.id', $courseId) // Lọc khóa học cụ thể
            ->where('ue.userid', $studentId) // Chỉ lấy dữ liệu cho sinh viên đã đăng ký
            ->orderBy('cs.section') // Thứ tự tuần
            ->get();
    }
}
