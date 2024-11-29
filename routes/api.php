<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\MoodleQAController;
use App\Http\Controllers\CourseInfoController;
use App\Http\Controllers\MoodleGradeController;
use App\Http\Controllers\MoodleAssignmentController;

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
Route::prefix('moodle')->group(function () {
     // Quên mật khẩu
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
     // Reset mật khẩu
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        // Cập nhật hồ sơ
        Route::put('/profile', [ProfileController::class, 'updateProfile']);
        // Thay đổi ngôn ngữ
        Route::patch('/language', [LanguageController::class, 'updateLanguage']);

        // lấy Danh sách bài tập, trạng thái, section và file bài nộp
        Route::get('/assignments-questions', [MoodleAssignmentController::class, 'getAssignmentsAndQuestions']);
        //download file đã nộp
        Route::get('/download/{contenthash}', [MoodleAssignmentController::class, 'getFile']);
        //submit bài tập
        Route::post('/submit-assignment', [MoodleAssignmentController::class, 'submitAssignment']);

        //lấy điểm 
        Route::get('/grade', [MoodleGradeController::class, 'getStudentGrades']);

        //lấy câu hỏi hỏi đáp
        Route::get('/questions', [MoodleQAController::class, 'getQuestions']);

        //thông tin khóa học
        Route::get('/course/{course_id}', [CourseInfoController::class, 'getCourseInfo']);

    });
});