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
            return response()->json($courses, 200);
        } catch (\Exception $e) {
            // Ghi log lỗi (nếu cần)
            // Log::error('Error fetching all courses: ' . $e->getMessage());

            // Trả về thông báo lỗi người dùng có thể hiểu
            return response()->json([
                'error' => 'There was an error fetching courses. Please try again later.'
            ], 500); // HTTP status code 500 - Internal Server Error
        }
    }

    public function getCoursesByStudentId(Request $request)
    {

        try {
            $studentId = $request->input('studentId');

            if (!is_numeric($studentId) || $studentId <= 0) {
                return response()->json(['error' => 'Invalid student ID.'], 400);
            }

            $courses = $this->courseService->getCoursesByStudentId((int)$studentId);

            if ($courses->isEmpty()) {
                return response()->json(['message' => 'No courses found for this student.'], 404);
            }

            return response()->json($courses, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not fetch courses for the student.'], 500);
        }
    }

    public function getDetailCoursesForStudent(Request $request)
    {
        try {
            // Lấy tham số đầu vào
            $studentId = $request->input('studentId');
            $courseId = $request->input('courseId');

            // Kiểm tra đầu vào
            if (!is_numeric($studentId) || $studentId <= 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid student ID. Student ID must be a positive number.'
                ], 400);
            }

            if (!is_numeric($courseId) || $courseId <= 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid course ID. Course ID must be a positive number.'
                ], 400);
            }

            // Gọi hàm từ service để lấy dữ liệu
            $courses = $this->courseService->getDetailCoursesForStudent((int)$courseId, (int)$studentId);

            // Kiểm tra dữ liệu trả về
            if ($courses->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No courses found for this student in the specified course.'
                ], 404);
            }

            // Trả về dữ liệu thành công
            return response()->json([
                'status' => 'success',
                'data' => $courses
            ], 200);
        } catch (\Exception $e) {
            // Ghi log lỗi nếu cần
            // \Log::error('Error fetching course details for student', [
            //     'studentId' => $studentId,
            //     'courseId' => $courseId,
            //     'error' => $e->getMessage()
            // ]);

            // Trả về lỗi chung
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching course details for the student.',
                'error' => $e->getMessage() // Có thể ẩn dòng này trong môi trường production để bảo mật
            ], 500);
        }
    }
}
