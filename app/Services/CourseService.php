<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use App\Repositories\CourseRepository;

class CourseService
{
    protected $courseRepository;

    public function __construct(CourseRepository $courseRepository)
    {
        $this->courseRepository = $courseRepository;
    }

    /**
     * Lấy danh sách tất cả khóa học (dùng cho trang chủ)
     *
     */
    public function getAllCourses()
    {
        $courses = $this->courseRepository->getAllCourses();
        $test = $this->formatCourses($courses);
        dd($test);
    }

    /**
     * Lấy các khóa học của sinh viên
     * 
     */
    public function getCoursesByStudentId()
    {
        $studentId = Auth::id();
        $courses = $this->courseRepository->getCoursesByStudentId($studentId);
        return $this->formatCourses($courses);
    }


    /**
     * Lấy các khóa học có thể đăng ký nếu sinh viên chưa tham gia khóa học nào
     *
     */
    public function getAvailableCoursesForStudent()
    {
        $studentId = Auth::id();
        $courses = $this->courseRepository->getAvailableCoursesForStudent($studentId);
        return $this->formatCourses($courses);
    }

    /**
     * Định dạng lại dữ liệu các khóa học
     * @param $courses
     * @return array
     */
    private function formatCourses($courses)
    {
        return $courses->map(function ($course) {

            return [
                'id' => $course->id,
                'fullname' => $course->fullname,
                'shortname' => $course->shortname,
                'category' => $course->category,
                'summary' => $course->summary,
                'startdate' => $course->startdate,
                'enddate' => $course->enddate,
                'teachers' => $course->teachers->pluck('fullname')->toArray(),
                'assistants' => $course->assistants->pluck('fullname')->toArray(),
                // 'modules' => $course->modules->pluck('id')->toArray(),
                'students_count' => $course->students->count(),
            ];
        });
    }
}
