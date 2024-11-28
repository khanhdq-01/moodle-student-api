<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\CourseService;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    protected $courseService;

    public function __construct(CourseService $courseService)
    {
        $this->courseService = $courseService;
    }

    public function getAllCourses()
    {
        $courses = $this->courseService->getAllCourses();
        return response()->json($courses);
    }

    public function getCoursesByStudentId()
    {
        $courses = $this->courseService->getCoursesByStudentId();
        return response()->json($courses);
    }

    public function getAvailableCoursesForStudent()
    {
        $courses = $this->courseService->getAvailableCoursesForStudent();
        return response()->json($courses);
    }
}
