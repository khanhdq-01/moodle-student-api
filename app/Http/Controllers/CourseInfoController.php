<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourseInfoController extends Controller
{
    public function getCourseInfo(Request $request, $course_id)
    {
        $user = $request->user();

        // Truy vấn thông tin khóa học
        $course = DB::select("
            SELECT 
                c.id AS course_id,
                c.fullname AS course_name,
                c.summary AS course_description,
                (SELECT CONCAT(u.firstname, ' ', u.lastname) 
                    FROM mdl_user u 
                    JOIN mdl_role_assignments ra ON u.id = ra.userid 
                    JOIN mdl_context ctx ON ra.contextid = ctx.id 
                    WHERE ctx.instanceid = c.id AND ra.roleid = 3
                    LIMIT 1) AS teacher_name,
                (SELECT CONCAT(u.firstname, ' ', u.lastname) 
                    FROM mdl_user u 
                    JOIN mdl_role_assignments ra ON u.id = ra.userid 
                    JOIN mdl_context ctx ON ra.contextid = ctx.id 
                    WHERE ctx.instanceid = c.id AND ra.roleid = 1
                    LIMIT 1) AS manager_name,
                (SELECT MIN(timestart) 
                    FROM mdl_event 
                    WHERE courseid = c.id AND eventtype = 'course') AS start_date,
                (SELECT MIN(timestart) 
                    FROM mdl_event 
                    WHERE courseid = c.id AND eventtype = 'exam') AS exam_date,
                (SELECT GROUP_CONCAT(mc.fullname SEPARATOR ', ') 
                    FROM mdl_course_modules cm
                    JOIN mdl_modules m ON cm.module = m.id
                    JOIN mdl_course mc ON cm.course = mc.id
                    WHERE cm.course = c.id) AS subjects
            FROM mdl_course c
            WHERE c.id = ?
        ", [$course_id]);

        // Kiểm tra nếu không tìm thấy khóa học
        if (empty($course)) {
            return response()->json(['message' => 'Không tìm thấy môn học'], 404);
        }

        $course = $course[0];

        return response()->json([
            'course_name' => $course->course_name,
            'course_description' => $course->course_description,
            'teacher_name' => $course->teacher_name,
            'manager_name' => $course->manager_name,
            'start_date' => $course->start_date ? date('d/m/Y', $course->start_date) : null,
            'exam_date' => $course->exam_date ? date('d/m/Y', $course->exam_date) : null,
            'subjects' => $course->subjects,
        ]);
    }
}