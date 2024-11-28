<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CourseController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Route::middleware('auth:api')->get('/courses', [CourseController::class, 'getCourses']);


//api courses 

// Route::prefix('api')->group(function () {
//     Route::get('courses', [CourseController::class, 'getAllCourses']);
//     Route::get('student-courses/{studentId}', [CourseController::class, 'getStudentCourses']);
//     Route::get('courses-for-enrollment/{studentId}', [CourseController::class, 'getAvailableCoursesForStudent']);
// });

// Route::middleware('auth:api')->group(function () {
//     Route::get('courses', [CourseController::class, 'getAllCourses']);  // Lấy tất cả khóa học
//     Route::get('my-courses', [CourseController::class, 'getCoursesByStudentId']);  // Lấy các khóa học của sinh viên
//     Route::get('available-courses', [CourseController::class, 'getAvailableCoursesForStudent']);  // Khóa học sinh viên có thể đăng ký
// });

Route::middleware('auth:api')->get('/courses', [CourseController::class, 'getAllCourses']);

// Để bỏ qua xác thực, bạn chỉ cần viết như sau
Route::get('/courses', [CourseController::class, 'getAllCourses']);
