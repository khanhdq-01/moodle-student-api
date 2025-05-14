<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\MoodleQAController;
use App\Http\Controllers\CourseInfoController;
use App\Http\Controllers\QuizController;
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

// Quên mật khẩu
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
// Reset mật khẩu
Route::post('/reset-password', [AuthController::class, 'resetPassword']);


Route::middleware(['auth:sanctum', 'student'])->group(function () {
    //user
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/get-user', [AuthController::class, 'getUser']); // lấy ra thông tin tài khoản đang đăng nhập
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    // Cập nhật hồ sơ
    Route::put('/profile', [ProfileController::class, 'updateProfile']);
    // Thay đổi ngôn ngữ
    Route::patch('/language', [LanguageController::class, 'updateLanguage']);

    Route::get('/assignment-and-quiz', [MoodleAssignmentController::class, 'getAssignmentsAndQuestions']); // lấy Danh sách bài tập, trạng thái, section và file bài nộp
    Route::get('/user-assign', [MoodleAssignmentController::class, 'getUserAssignments']);//lấy danh sách bài tập tự luận
    Route::get('/user-assign/{course_id}', [MoodleAssignmentController::class, 'getCourseAssignments']);//lấy danh sách bài tập tự luận theo id khóa học
    Route::get('/assign-detail/{id}', [MoodleAssignmentController::class, 'getAssignmentsDetail']);//lấy chi tiết bài tập tự luận theo id bài tập

    Route::get('/quizzes', [MoodleAssignmentController::class, 'getUserQuizzes']);//lấy danh sách bài tập trắc nhiệm
    Route::get('/quizzes/{course_id}', [MoodleAssignmentController::class, 'getCourseQuizzes']);//lấy danh sách bài tập trắc nhiệm theo id khóa học
    Route::get('/quizzes-detail/{id}', [MoodleAssignmentController::class, 'getQuizQuestionsDetail']);//lấy chi tiết bài tập trắc nghiệm theo id bài tập

    Route::post('quizzes/{quizId}/start', [QuizController::class, 'startAttempt']); //Bắt đầu làm bài (attempt)(Tạo session làm bài, cấu hình các câu hỏi và trạng thái khởi tạo vào atempt)
    Route::post('quizzes/{quizId}/answer', [QuizController::class, 'submitAnswer']);//Gửi câu trả lời lưu vào attampt(Ghi lại đáp án người dùng chọn)
    Route::post('quizzes/{quizId}/finish', [QuizController::class, 'finishAttempt']); //Kết thúc nộp bài(Cập nhật trạng thái finished, lưu thời gian kết thúc)
    Route::get('quizzes/{quizId}/result', [QuizController::class, 'getResult']); //Lấy kết quả(Truy xuất điểm số và trạng thái lần làm bài gần nhất)
   
    Route::get('/download/{contenthash}', [MoodleAssignmentController::class, 'getFile']);  //download file đã nộp
    Route::post('/upload-file', [MoodleAssignmentController::class, 'uploadFile']); //upload file bài tập
    Route::post('/submit-assignment', [MoodleAssignmentController::class, 'submitAssignment']);//submit bài tập

    //lấy điểm 
    Route::get('/grades/assignments', [MoodleGradeController::class, 'getAssignmentGrades']); //lấy điểm bài tập tự luận
    Route::get('/grades/quizzes', [MoodleGradeController::class, 'getQuizGrades']); //lấy điểm bài tập trắc nghiệm

    //lấy câu hỏi hỏi đáp trong thảo luận
    Route::get('/questions', [MoodleQAController::class, 'getQuestions']); //hỏi đáp trong thảo luận

    //thông tin khóa học
    Route::get('courses', [CourseController::class, 'getAllCourses']);
    Route::get('student-courses-detail', [CourseController::class, 'getDetailCoursesForStudent']);
    Route::get('student-courses/{studentId}', [CourseController::class, 'getCoursesByStudentId']);

    //thông tin diễn đàn
    Route::get('forums', [ForumController::class, 'getForums']); //thông tin diễn đàn mà học sinh đăng nhập
    Route::get('/forums/{forum_id}/discussions', [ForumController::class, 'getDiscussions']); //lấy ra bài thảo luận trong diễn đàn
    Route::post('/forums/{forum_id}/discussions', [ForumController::class, 'createDiscussion']); //tạo bài thảo luận mới trong diễn đàn
    Route::get('/forums/{forum_id}/discussions/{discussion_id}', [ForumController::class, 'getDiscussionDetails']); //lấy ra chi tiết bài thảo luận trong diễn đàn (cả bình luận,...)
    Route::post('/forums/{forum_id}/discussions/{discussion_id}/comments', [ForumController::class, 'postComment']); //tạo bình luận trong bài thảo luận trong diễn đàn
    Route::delete('/forums/{forum_id}/discussions/{discussion_id}/comments/{comment_id}', [ForumController::class, 'deleteComment']); //xóa bình luận trong bài thảo luận trong diễn đàn

    //lấy danh sách lịch/sự kiện trong khoảng thời gian
    Route::get('events', [EventController::class, 'index']);

});
