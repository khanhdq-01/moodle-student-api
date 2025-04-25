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
                    ->where('ctx.contextlevel', '=', 50);
            })
            ->leftJoin('mdl_role_assignments as ra', 'ra.contextid', '=', 'ctx.id')
            ->leftJoin('mdl_user as u', function ($join) {
                $join->on('ra.userid', '=', 'u.id')
                    ->where('ra.roleid', '=', 3); // Giảng viên
            })
            ->leftJoin('mdl_user as a', function ($join) {
                $join->on('ra.userid', '=', 'a.id')
                    ->where('ra.roleid', '=', 4); // Trợ giảng
            })
            ->leftJoin('mdl_course_categories as cc', 'cc.id', '=', 'c.category')
            ->join('mdl_enrol as e', 'e.courseid', '=', 'c.id')
            ->join('mdl_user_enrolments as ue', 'ue.enrolid', '=', 'e.id')
            ->leftJoin('mdl_files as f', function ($join) {
                $join->on('f.contextid', '=', 'ctx.id')
                    ->where('f.component', '=', 'course')
                    ->where('f.filearea', '=', 'overviewfiles')
                    ->where('f.filename', '!=', '.'); // Bỏ file rỗng
            })
            ->where('ue.userid', '=', $studentId)
            ->select(
                'c.id as course_id',
                'c.fullname as course_name',
                'c.summary as description',
                'cc.name as category_name',
                DB::raw('(SELECT COUNT(*) FROM mdl_course_modules cm WHERE cm.course = c.id) AS lesson_count'),
                DB::raw('(SELECT COUNT(*) FROM mdl_user_enrolments ue2 
                      JOIN mdl_enrol e2 ON ue2.enrolid = e2.id WHERE e2.courseid = c.id) AS student_count'),
                DB::raw('GROUP_CONCAT(DISTINCT CONCAT(u.firstname, " ", u.lastname, " (Giáo viên)") SEPARATOR ", ") AS teachers'),
                DB::raw('GROUP_CONCAT(DISTINCT CONCAT(a.firstname, " ", a.lastname, " (Trợ giảng)") SEPARATOR ", ") AS assistants'),
                'f.filename as image_name',
                'f.contenthash as image_contenthash'
            )
            ->groupBy('c.id', 'c.fullname', 'c.summary', 'cc.name', 'f.filename', 'f.contenthash')
            ->get();
    }
    
    public function getDetailCoursesForStudent($courseId, $studentId)
    {
        // === Lấy ảnh môn học ===
        $courseImage = DB::table('mdl_context as ctx')
            ->join('mdl_files as f', 'f.contextid', '=', 'ctx.id')
            ->where('ctx.instanceid', $courseId)
            ->where('ctx.contextlevel', 50)
            ->where('f.component', 'course')
            ->where('f.filearea', 'overviewfiles')
            ->where('f.filename', '<>', '.')
            ->select('f.filename', 'f.contenthash')
            ->first();
    
        // === Lấy unilabel (intro + lecture), section, module ===
        $unilabels = DB::table('mdl_unilabel as ul')
            ->leftJoin('mdl_unilabeltype_courseintro as ci', function ($join) {
                $join->on('ci.unilabelid', '=', 'ul.id')
                    ->where('ul.unilabeltype', '=', 'courseintro');
            })
            ->leftJoin('mdl_unilabeltype_lecture as lec', function ($join) {
                $join->on('lec.unilabelid', '=', 'ul.id')
                    ->where('ul.unilabeltype', '=', 'lecture');
            })
            ->join('mdl_course_modules as cm', function ($join) {
                $join->on('cm.instance', '=', 'ul.id')
                    ->whereRaw("cm.module = (SELECT id FROM mdl_modules WHERE name = 'unilabel' LIMIT 1)");
            })
            ->join('mdl_modules as md', 'cm.module', '=', 'md.id')
            ->join('mdl_course_sections as cs', 'cs.id', '=', 'cm.section')
            ->where('ul.course', $courseId)
            ->select(
                'ul.id as unilabel_id',
                'ul.name as unilabel_name',
                'ul.intro as unilabel_intro',
                'ul.unilabeltype',
                'cs.id as section_id',
                'cs.name as section_name',
                'md.name as module_name',
                'ul.unilabeltype as unilabel_type',
                'lec.content as lecture_content'
            )
            ->orderBy('cs.section')
            ->get();
    
        // === Nhóm kết quả theo section_id ===
        $grouped = [];
    
        foreach ($unilabels as $item) {
            $sectionId = $item->section_id;
    
            if (!isset($grouped[$sectionId])) {
                $grouped[$sectionId] = [
                    'section_id' => $sectionId,
                    'section_name' => $item->section_name,
                    'module_name' => $item->module_name,
                    'unilabel_type' => $item->unilabel_type,
                    'course_intro' => null,
                    'lessons' => []
                ];
            }
    
            if ($item->unilabeltype === 'courseintro') {
                $grouped[$sectionId]['course_intro'] = [
                    'unilabel_id' => $item->unilabel_id,
                    'name' => $item->unilabel_name,
                    'intro' => $item->unilabel_intro
                ];
            } elseif ($item->unilabeltype === 'lecture') {
                $grouped[$sectionId]['lessons'][] = [
                    'id' => $item->unilabel_id,
                    'name' => $item->unilabel_name,
                    'intro' => $item->unilabel_intro,
                    'content' => $item->lecture_content
                ];
            }
        }
    
        return response()->json([
            'course_image' => $courseImage ? [
                'filename' => $courseImage->filename,
                'contenthash' => $courseImage->contenthash
            ] : null,
            'data' => array_values($grouped)
        ]);
    }
}
