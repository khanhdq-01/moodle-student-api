<?php

namespace App\Http\Controllers;

use Log;
use Illuminate\Http\Request;
use App\Services\CourseService;
use App\Http\Controllers\Controller;

class CourseController extends Controller
{
    protected $courseService;

    public function __construct(CourseService $courseService)
    {
        $this->courseService = $courseService;
    }

    public function getAllCourses()
    {

        try {
            $courses = $this->courseService->getAllCourses();
            return response()->json($courses);
        } catch (\Exception $e) {
            // Ghi log lỗi (nếu cần)
            // Log::error('Error fetching all courses: ' . $e->getMessage());

            // Trả về thông báo lỗi người dùng có thể hiểu
            return response()->json([
                'error' => 'There was an error fetching courses. Please try again later.'
            ], 500); // HTTP status code 500 - Internal Server Error
        }
    }

    // public function getCoursesByStudentId($studentId)
    // {
    //     $courses = $this->courseService->getCoursesByStudentId($studentId);
    //     return response()->json($courses);
    // }

    // public function getAvailableCoursesForStudent()
    // {
    //     $courses = $this->courseService->getAvailableCoursesForStudent();
    //     return response()->json($courses);
    // }
}
