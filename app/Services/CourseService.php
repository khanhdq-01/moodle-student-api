<?php

namespace App\Services;


use App\Interfaces\CourseRepositoryInterface;

class CourseService
{
    protected $courseRepository;

    public function __construct(CourseRepositoryInterface $courseRepository)
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
        return $courses;
    }

    /**
     * Lấy các khóa học của sinh viên
     * 
     */
    public function getCoursesByStudentId($studentId)
    {
        $courses = $this->courseRepository->getCoursesByStudentId($studentId);
        return $courses;
    }


    /**
     * Lấy các khóa học có thể đăng ký nếu sinh viên chưa tham gia khóa học nào
     *
     */
    public function getDetailCoursesForStudent($courseId, $studentId)
    {
        $courses = $this->courseRepository->getDetailCoursesForStudent($courseId, $studentId);
        dd($courses);
        return $courses;
    }
}
