<?php

namespace App\Http\Controllers;

use App\Models\MoodleAssignment;
use App\Models\MoodleCourse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MoodleAssignmentController extends Controller
{
    public function getAssignmentsAndQuestions(Request $request)
    {
        $user = $request->user();
    
        $moodleUser = User::where('username', $user->username)->first();
        if (!$moodleUser) {
            return response()->json(['message' => 'User not found in Moodle'], 404);
        }
    
        // Lấy danh sách khóa học của user
        $courses = DB::table('mdl_user_enrolments')
            ->join('mdl_enrol', 'mdl_user_enrolments.enrolid', '=', 'mdl_enrol.id')
            ->where('mdl_user_enrolments.userid', $moodleUser->id)
            ->pluck('mdl_enrol.courseid')
            ->toArray();
    
        if (empty($courses)) {
            return response()->json([
                'code' => 200,
                'message' => 'User chưa ghi danh vào khóa học nào',
                'data' => [
                    'assignments' => [],
                    'questions' => []
                ]
            ]);
        }
    
        /** ========================== Lấy danh sách bài tập (Assignment) ========================== **/
        $assignments = DB::table('mdl_assign')
            ->join('mdl_course_modules', 'mdl_assign.id', '=', 'mdl_course_modules.instance')
            ->join('mdl_course_sections', 'mdl_course_modules.section', '=', 'mdl_course_sections.id')
            ->whereIn('mdl_assign.course', $courses)
            ->select(
                'mdl_assign.id',
                'mdl_assign.course',
                'mdl_assign.name',
                'mdl_assign.intro as description',
                DB::raw("DATE_FORMAT(FROM_UNIXTIME(mdl_assign.duedate), '%d/%m/%Y') as duedate"),
                'mdl_course_sections.name as section_name',
                'mdl_course_modules.section'
            )
            ->get();
    
        $assignmentIds = $assignments->pluck('id')->toArray();
    
        // Lấy danh sách bài nộp của user
        $submissions = DB::table('mdl_assign_submission')
            ->whereIn('assignment', $assignmentIds)
            ->where('userid', $moodleUser->id)
            ->select('id', 'assignment', 'status')
            ->get();
    
        $submissionIds = $submissions->pluck('id')->toArray();
    
        // Lấy danh sách file nộp bài
        $files = DB::table('mdl_files')
            ->where('component', 'assignsubmission_file')
            ->where('filearea', 'submission_files')
            ->whereIn('itemid', $submissionIds)
            ->select('id', 'filename', 'contenthash', 'mimetype', 'filesize', 'timemodified', 'itemid')
            ->get();
    
        // Nhóm file theo `submission_id`
        $filesBySubmission = [];
        foreach ($files as $file) {
            $filesBySubmission[$file->itemid][] = [
                'filename' => $file->filename,
                'url' => url("/moodledata/{$file->contenthash}"),
                'mimetype' => $file->mimetype,
                'filesize' => $file->filesize,
                'timemodified' => $file->timemodified
            ];
        }
    
        // Gán trạng thái & file vào assignments
        foreach ($assignments as $assignment) {
            $assignment->status = 'chưa nộp'; // Mặc định nếu không có submission
            $assignment->files = [];
    
            foreach ($submissions as $submission) {
                if ($submission->assignment == $assignment->id) {
                    $assignment->status = $submission->status;
                    $assignment->files = $filesBySubmission[$submission->id] ?? [];
                }
            }
        }
    
        /** ========================== Lấy danh sách câu hỏi & đáp án (Question & Answers) ========================== **/
        $questions = DB::table('mdl_question')
        ->join('mdl_quiz_slots', 'mdl_question.id', '=', 'mdl_quiz_slots.questionid')
        ->join('mdl_quiz', 'mdl_quiz_slots.quizid', '=', 'mdl_quiz.id')
        ->join('mdl_course_modules', function ($join) {
            $join->on('mdl_quiz.id', '=', 'mdl_course_modules.instance')
                ->where('mdl_course_modules.module', '=', function ($query) {
                    $query->select('id')->from('mdl_modules')->where('name', 'quiz');
                });
        })
        ->join('mdl_course_sections', 'mdl_course_modules.section', '=', 'mdl_course_sections.id')
        ->whereIn('mdl_quiz.course', $courses)
        ->whereIn('mdl_question.qtype', ['multichoice', 'truefalse', 'random'])
        ->select(
            'mdl_question.id',
            'mdl_question.name as question_name',
            'mdl_question.qtype',
            'mdl_question.questiontext',
            'mdl_quiz.name as quiz_name',
            'mdl_quiz.course',
            DB::raw("DATE_FORMAT(FROM_UNIXTIME(mdl_quiz.timeopen), '%d/%m/%Y') as duedate"),
            'mdl_course_sections.name as section_name',
            'mdl_course_modules.section'
        )
        ->get();
    
    
        // Lấy danh sách ID câu hỏi
        $questionIds = $questions->pluck('id')->toArray();
    
        // Lấy danh sách đáp án từ bảng `mdl_question_answers`
        $answers = DB::table('mdl_question_answers')
            ->whereIn('question', $questionIds)
            ->select('id', 'question', 'answer', 'fraction') // `fraction = 1.0` là đáp án đúng
            ->get();
    
        // Nhóm đáp án theo ID câu hỏi
        $answersByQuestion = [];
        foreach ($answers as $answer) {
            $answersByQuestion[$answer->question][] = [
                'id' => $answer->id,
                'answer' => $answer->answer,
                'is_correct' => $answer->fraction == 1.0 // Xác định đáp án đúng
            ];
        }
    
        // Gán danh sách đáp án vào câu hỏi
        foreach ($questions as $question) {
            $question->answers = $answersByQuestion[$question->id] ?? [];
        }
    
        return response()->json([
            'code' => 200,
            'message' => 'Danh sách bài tập và câu hỏi của user',
            'data' => [
                'assignments' => $assignments,
                'questions' => $questions
            ]
        ]);
    }

    public function getFile($contenthash)
    {
        $moodleDataPath = 'C:/xampp/moodledata/filedir';

        $filePath = "{$moodleDataPath}/" . substr($contenthash, 0, 2) . "/" . substr($contenthash, 2, 2) . "/{$contenthash}";
    
        if (!file_exists($filePath)) {
            return response()->json(['message' => 'File not found in Moodle storage'], 404);
        }
    
        // Lấy MIME type từ database
        $file = DB::table('mdl_files')->where('contenthash', $contenthash)->first();
        $mimeType = $file->mimetype ?? 'application/octet-stream';
    
        // Trả file về cho client
        $filename = $file->filename ?? 'downloaded_file';

        // Trả file về client dưới dạng download
        return response()->download($filePath, $filename, [
            'Content-Type' => $mimeType,
        ]);
    }

    public function submitAssignment(Request $request)
    {
        $user = $request->user();
    
        // Kiểm tra user có tồn tại không
        $moodleUser = User::where('username', $user->username)->first();
        if (!$moodleUser) {
            return response()->json(['message' => 'User not found in Moodle'], 404);
        }
    
        // Kiểm tra request có file không
        if (!$request->hasFile('file')) {
            return response()->json(['message' => 'Không có file để nộp bài'], 400);
        }
    
        $assignmentId = $request->assignment_id;
    
        // Kiểm tra bài tập có tồn tại không
        $assignment = DB::table('mdl_assign')->where('id', $assignmentId)->first();
        if (!$assignment) {
            return response()->json(['message' => 'Assignment không tồn tại'], 404);
        }
    
        // Kiểm tra xem user đã có submission chưa
        $existingSubmission = DB::table('mdl_assign_submission')
            ->where('assignment', $assignmentId)
            ->where('userid', $moodleUser->id)
            ->first();
    
        if ($existingSubmission) {
            // Cập nhật submission đã có
            DB::table('mdl_assign_submission')
                ->where('id', $existingSubmission->id)
                ->update(['timemodified' => time()]);
    
            $submissionId = $existingSubmission->id;
        } else {
            // Tạo submission mới
            $submissionId = DB::table('mdl_assign_submission')->insertGetId([
                'assignment' => $assignmentId,
                'userid' => $moodleUser->id,
                'status' => 'submitted',
                'timemodified' => time(),
            ]);
        }

        $savedFiles = [];

        // Kiểm tra nếu chỉ có một file, chuyển thành mảng
        $files = $request->file('file');
        if (!is_array($files)) {
            $files = [$files];
        }

        // Xử lý từng file
        foreach ($files as $file) {
            // Tạo contenthash (SHA1 hash của nội dung file)
            $content = file_get_contents($file->getRealPath());
            $contenthash = sha1($content);

            // Đường dẫn thư mục theo Moodle
            $moodleDataPath = 'C:/xampp/moodledata/filedir';
            $dir1 = substr($contenthash, 0, 2);
            $dir2 = substr($contenthash, 2, 2);
            $fileDir = "{$moodleDataPath}/{$dir1}/{$dir2}";
            $filePath = "{$fileDir}/{$contenthash}";
    
            // Tạo thư mục nếu chưa tồn tại
            if (!file_exists($fileDir)) {
                mkdir($fileDir, 0777, true);
            }
    
            // Lưu file vào thư mục Moodle
            if (file_put_contents($filePath, $content) === false) {
                return response()->json(['message' => 'Không thể lưu file vào Moodle storage'], 500);
            }
    
            // Lưu thông tin file vào database
            DB::table('mdl_files')->insert([
                'contenthash' => $contenthash,
                'contextid' => 1, // ID context của khóa học, cần lấy đúng context_id
                'component' => 'assignsubmission_file',
                'filearea' => 'submission_files',
                'itemid' => $submissionId,
                'filename' => $file->getClientOriginalName(),
                'mimetype' => $file->getClientMimeType(),
                'filesize' => $file->getSize(),
                'timemodified' => time(),
                'timecreated' => time(),
            ]);
    
            $savedFiles[] = [
                'filename' => $file->getClientOriginalName(),
                'contenthash' => $contenthash,
            ];
        }
    
        return response()->json([
            'code' => 200,
            'message' => 'Nộp bài thành công',
            'submission_id' => $submissionId,
            'files' => $savedFiles // Danh sách file đã lưu
        ]);
    }
}
