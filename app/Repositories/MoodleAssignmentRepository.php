<?php

namespace App\Repositories;

use App\Interfaces\MoodleAssignmentInterface;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;

class MoodleAssignmentRepository implements MoodleAssignmentInterface
{
    public function getAssignmentsAndQuestions($user)
    {
        $moodleUser = User::where('username', $user->username)->first();
        if (!$moodleUser) {
            return ['error' => true, 'message' => 'User not found in Moodle'];
        }

        $courses = DB::table('mdl_user_enrolments')
            ->join('mdl_enrol', 'mdl_user_enrolments.enrolid', '=', 'mdl_enrol.id')
            ->where('mdl_user_enrolments.userid', $moodleUser->id)
            ->pluck('mdl_enrol.courseid')
            ->toArray();

        if (empty($courses)) {
            return ['error' => false, 'assignments' => [], 'questions' => [], 'message' => 'User chưa ghi danh vào khóa học nào'];
        }

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
                'mdl_course_modules.section',
            )
            ->get();

        $assignmentIds = $assignments->pluck('id')->toArray();

        $submissions = DB::table('mdl_assign_submission')
            ->whereIn('assignment', $assignmentIds)
            ->where('userid', $moodleUser->id)
            ->select('id', 'assignment', 'status')
            ->get();

        $submissionIds = $submissions->pluck('id')->toArray();

        $files = DB::table('mdl_files')
            ->where('component', 'assignsubmission_file')
            ->where('filearea', 'submission_files')
            ->whereIn('itemid', $submissionIds)
            ->select('id', 'filename', 'contenthash', 'mimetype', 'filesize', 'timemodified', 'itemid')
            ->get();

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

        foreach ($assignments as $assignment) {
            $assignment->status = 'chưa nộp';
            $assignment->files = [];
            foreach ($submissions as $submission) {
                if ($submission->assignment == $assignment->id) {
                    $assignment->status = $submission->status;
                    $assignment->files = $filesBySubmission[$submission->id] ?? [];
                }
            }
        }

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

        $questionIds = $questions->pluck('id')->toArray();

        $answers = DB::table('mdl_question_answers')
            ->whereIn('question', $questionIds)
            ->select('id', 'question', 'answer', 'fraction')
            ->get();

        $answersByQuestion = [];
        foreach ($answers as $answer) {
            $answersByQuestion[$answer->question][] = [
                'id' => $answer->id,
                'answer' => $answer->answer,
                'is_correct' => $answer->fraction == 1.0
            ];
        }

        foreach ($questions as $question) {
            $question->answers = $answersByQuestion[$question->id] ?? [];
        }

        $groupedQuestions = [];
        foreach ($questions as $question) {
            if (!isset($groupedQuestions[$question->quiz_name])) {
                $groupedQuestions[$question->quiz_name] = [
                    'quiz_name' => $question->quiz_name,
                    'quiz_list' => []
                ];
            }

            $groupedQuestions[$question->quiz_name]['quiz_list'][] = [
                'id' => $question->id,
                'qtype' => $question->qtype,
                'questiontext' => $question->questiontext,
                'course' => $question->course,
                'duedate' => $question->duedate,
                'section_name' => $question->section_name,
                'section' => $question->section,
                'answers' => $question->answers
            ];
        }

        return [
            'error' => false,
            'assignments' => $assignments,
            'questions' => array_values($groupedQuestions),
            'message' => 'Danh sách bài tập và câu hỏi của user'
        ];
    }

    public function getFile($contenthash)
    {
        $moodleDataPath = 'C:/xampp/moodledata/filedir';
        $subPath = substr($contenthash, 0, 2) . '/' . substr($contenthash, 2, 2) . '/' . $contenthash;
        $filePath = "{$moodleDataPath}/{$subPath}";
    
        if (!file_exists($filePath)) {
            return ['error' => true, 'message' => 'File not found in Moodle storage'];
        }
    
        // Lấy MIME type từ database
        $file = DB::table('mdl_files')->where('contenthash', $contenthash)->first();

        if (!$file) {
            return ['error' => true, 'message' => 'File metadata not found'];
        }

        $mimeType = $file->mimetype ?? 'application/octet-stream';
        $filename = $file->filename ?? 'downloaded_file';
        
        return [
            'error' => false,
            'file' => $filePath,
            'mimeType' => $mimeType,
            'filename' => $filename,
            'fileSize' => filesize($filePath)
        ];
    }

    public function uploadFile($file, $assignmentId, $userId)
    {
        // 1. Lấy hoặc tạo submission
        $submission = DB::table('mdl_assign_submission')
            ->where('assignment', $assignmentId)
            ->where('userid', $userId)
            ->where('latest', 1)
            ->first();

        if (!$submission) {
            $submissionId = DB::table('mdl_assign_submission')->insertGetId([
                'assignment' => $assignmentId,
                'userid' => $userId,
                'status' => 'new',
                'timemodified' => time(),
                'timecreated' => time(),
                'latest' => 1,
                'groupid' => 0,
                'attemptnumber' => 0,
            ]);
        } else {
            $submissionId = $submission->id;
        }

        // 2. Lấy contextid của bài tập
        $context = DB::table('mdl_course_modules as cm')
            ->join('mdl_context as ctx', 'ctx.instanceid', '=', 'cm.id')
            ->join('mdl_assign as a', 'a.id', '=', 'cm.instance')
            ->where('a.id', $assignmentId)
            ->where('ctx.contextlevel', 70)
            ->select('ctx.id as context_id')
            ->first();

        if (!$context) {
            return ['error' => true, 'message' => 'Context not found'];
        }

        $contextId = $context->context_id;

        // 3. Lưu file vào thư mục moodledata
        $fileContent = file_get_contents($file);
        $contenthash = sha1($fileContent);
        $moodleDataPath = 'C:/xampp/moodledata/filedir';
        $subfolder1 = substr($contenthash, 0, 2);
        $subfolder2 = substr($contenthash, 2, 2);
        $storagePath = "$moodleDataPath/$subfolder1/$subfolder2";

        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0777, true);
        }

        $storedPath = "$storagePath/$contenthash";
        if (!file_exists($storedPath)) {
            file_put_contents($storedPath, $fileContent);
        }

        $filename = trim($file->getClientOriginalName()); // Loại bỏ khoảng trắng
        $filepath = '/'; // Khởi tạo filepath

        // 4. Kiểm tra và xử lý file trùng lặp
        $existingFile = DB::table('mdl_files')->where([
            ['contextid', '=', $contextId],
            ['component', '=', 'assignsubmission_file'],
            ['filearea', '=', 'submission_files'],
            ['itemid', '=', $submissionId],
            ['filepath', '=', $filepath],
            ['filename', '=', $filename],
        ])->first();

        if ($existingFile) {
            DB::table('mdl_files')->where('id', $existingFile->id)->update([
                'contenthash' => $contenthash,
                'filesize' => $file->getSize(),
                'mimetype' => $file->getMimeType(),
                'timemodified' => time(),
            ]);
            return ['error' => false, 'message' => 'File updated successfully'];
        }

        // 5. Chèn bản ghi mới vào mdl_files
        try {
            DB::table('mdl_files')->insert([
                'contenthash' => $contenthash,
                'pathnamehash' => sha1($filepath . $filename),
                'filename' => $filename,
                'filepath' => $filepath,
                'filesize' => $file->getSize(),
                'mimetype' => $file->getMimeType(),
                'timecreated' => time(),
                'timemodified' => time(),
                'userid' => $userId,
                'component' => 'assignsubmission_file',
                'filearea' => 'submission_files',
                'itemid' => $submissionId,
                'contextid' => $contextId,
                'status' => 0,
                'source' => null,
                'author' => null,
                'license' => null,
                'sortorder' => 0,
            ]);
            return ['error' => false, 'message' => 'File uploaded successfully'];
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == 23000) {
                // Duplicate file detection
                $duplicateFile = DB::table('mdl_files')->where([
                    ['contextid', '=', $contextId],
                    ['component', '=', 'assignsubmission_file'],
                    ['filearea', '=', 'submission_files'],
                    ['itemid', '=', $submissionId],
                    ['filepath', '=', $filepath],
                    ['filename', '=', $filename],
                ])->first();

                return ['error' => true, 'message' => 'File already exists'];
            }

            throw $e;
        }
    }

    public function submitAssignment(Request $request)
    {
        $user = $request->user();

        $moodleUser = DB::table('users')->where('username', $user->username)->first();
        if (!$moodleUser) {
            throw new \Exception('User not found in Moodle', 404);
        }

        if (!$request->hasFile('file')) {
            throw new \Exception('Không có file để nộp bài', 400);
        }

        $assignmentId = $request->assignment_id;

        $assignment = DB::table('mdl_assign')->where('id', $assignmentId)->first();
        if (!$assignment) {
            throw new \Exception('Assignment không tồn tại', 404);
        }

        $existingSubmission = DB::table('mdl_assign_submission')
            ->where('assignment', $assignmentId)
            ->where('userid', $moodleUser->id)
            ->first();

        if ($existingSubmission) {
            DB::table('mdl_assign_submission')
                ->where('id', $existingSubmission->id)
                ->update(['timemodified' => time()]);
            $submissionId = $existingSubmission->id;
        } else {
            $submissionId = DB::table('mdl_assign_submission')->insertGetId([
                'assignment' => $assignmentId,
                'userid' => $moodleUser->id,
                'status' => 'submitted',
                'timemodified' => time(),
            ]);
        }

        $savedFiles = [];
        $files = $request->file('file');
        if (!is_array($files)) {
            $files = [$files];
        }

        foreach ($files as $file) {
            $content = file_get_contents($file->getRealPath());
            $contenthash = sha1($content);

            $moodleDataPath = 'C:/xampp/moodledata/filedir';
            $dir1 = substr($contenthash, 0, 2);
            $dir2 = substr($contenthash, 2, 2);
            $fileDir = "{$moodleDataPath}/{$dir1}/{$dir2}";
            $filePath = "{$fileDir}/{$contenthash}";

            if (!file_exists($fileDir)) {
                mkdir($fileDir, 0777, true);
            }

            if (file_put_contents($filePath, $content) === false) {
                throw new \Exception('Không thể lưu file vào Moodle storage', 500);
            }

            DB::table('mdl_files')->insert([
                'contenthash' => $contenthash,
                'contextid' => 1,
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

        return [
            'submission_id' => $submissionId,
            'files' => $savedFiles,
        ];
    }
    public function getUserAssignments(Request $request)
    {
        $user = $request->user();
        $moodleUser = User::where('username', $user->username)->first();
        if (!$moodleUser) {
            return ['error' => true, 'message' => 'User not found in Moodle', 'status' => 404];
        }

        $courses = DB::table('mdl_user_enrolments')
            ->join('mdl_enrol', 'mdl_user_enrolments.enrolid', '=', 'mdl_enrol.id')
            ->where('mdl_user_enrolments.userid', $moodleUser->id)
            ->pluck('mdl_enrol.courseid')
            ->toArray();

        $assignments = DB::table('mdl_assign')
            ->join('mdl_course_modules', 'mdl_assign.id', '=', 'mdl_course_modules.instance')
            ->join('mdl_course_sections', 'mdl_course_modules.section', '=', 'mdl_course_sections.id')
            ->whereIn('mdl_assign.course', $courses)
            ->select(
                'mdl_assign.id',
                'mdl_assign.name',
                'mdl_assign.course',
                DB::raw("DATE_FORMAT(FROM_UNIXTIME(mdl_assign.duedate), '%d/%m/%Y') as duedate"),
                'mdl_course_modules.section',
                'mdl_course_sections.name as section_name'
            )
            ->get();

        $assignmentIds = $assignments->pluck('id')->toArray();

        $submissions = DB::table('mdl_assign_submission')
            ->whereIn('assignment', $assignmentIds)
            ->where('userid', $moodleUser->id)
            ->get();

        $submissionMap = $submissions->keyBy('assignment');

        $textSubmissions = DB::table('mdl_assignsubmission_onlinetext')
            ->whereIn('submission', $submissions->pluck('id')->toArray())
            ->get()
            ->keyBy('submission');

        foreach ($assignments as $assignment) {
            $assignment->status = 0;
            $assignment->text = '';

            if (isset($submissionMap[$assignment->id])) {
                $submission = $submissionMap[$assignment->id];
                $assignment->status = $submission->status === 'submitted' ? 1 : 0;
                $assignment->text = data_get($textSubmissions, $submission->id . '.onlinetext', '');
            }
        }

        return [
            'error' => false,
            'status' => 200,
            'message' => 'Danh sách bài tự luận',
            'data' => $assignments
        ];
    }

    public function getUserQuizzes(Request $request)
    {
        $user = $request->user();
        $moodleUser = User::where('username', $user->username)->first();

        if (!$moodleUser) {
            return ['error' => true, 'message' => 'User not found in Moodle'];
        }

        $courses = DB::table('mdl_user_enrolments')
            ->join('mdl_enrol', 'mdl_user_enrolments.enrolid', '=', 'mdl_enrol.id')
            ->where('mdl_user_enrolments.userid', $moodleUser->id)
            ->pluck('mdl_enrol.courseid')
            ->toArray();

        $quizzes = DB::table('mdl_quiz')
            ->join('mdl_course_modules', 'mdl_quiz.id', '=', 'mdl_course_modules.instance')
            ->join('mdl_course_sections', 'mdl_course_modules.section', '=', 'mdl_course_sections.id')
            ->whereIn('mdl_quiz.course', $courses)
            ->select(
                'mdl_quiz.id',
                'mdl_quiz.name',
                'mdl_quiz.course as course_id',
                DB::raw("DATE_FORMAT(FROM_UNIXTIME(mdl_quiz.timeopen), '%d/%m/%Y') as open_time"),
                DB::raw("DATE_FORMAT(FROM_UNIXTIME(mdl_quiz.timeclose), '%d/%m/%Y') as close_time"),
                'mdl_course_modules.section',
                'mdl_course_sections.name as section_name',
                DB::raw("ROUND(mdl_quiz.grade, 2) as grade")
            )
            ->get();

        $quizIds = $quizzes->pluck('id')->toArray();

        $attempts = DB::table('mdl_quiz_attempts')
            ->whereIn('quiz', $quizIds)
            ->where('userid', $moodleUser->id)
            ->select('quiz', 'state')
            ->get();

        $attemptMap = [];
        foreach ($attempts as $attempt) {
            $attemptMap[$attempt->quiz] = $attempt->state === 'finished' ? 1 : 0;
        }

        foreach ($quizzes as $quiz) {
            $quiz->status = $attemptMap[$quiz->id] ?? 0;
        }

        return ['error' => false, 'data' => $quizzes];
    }

    public function getCourseAssignments($courseId, Request $request)
    {
        $user = $request->user();
        $moodleUser = User::where('username', $user->username)->first();

        if (!$moodleUser) {
            return ['error' => true, 'message' => 'User not found in Moodle'];
        }

        $assignments = DB::table('mdl_assign')
            ->join('mdl_course_modules', 'mdl_assign.id', '=', 'mdl_course_modules.instance')
            ->join('mdl_course_sections', 'mdl_course_modules.section', '=', 'mdl_course_sections.id')
            ->where('mdl_assign.course', $courseId)
            ->select(
                'mdl_assign.id',
                'mdl_assign.name',
                'mdl_assign.course',
                DB::raw("DATE_FORMAT(FROM_UNIXTIME(mdl_assign.duedate), '%d/%m/%Y') as duedate"),
                'mdl_course_modules.section',
                'mdl_course_sections.name as section_name'
            )
            ->get();

        $assignmentIds = $assignments->pluck('id')->toArray();

        $submissions = DB::table('mdl_assign_submission')
            ->whereIn('assignment', $assignmentIds)
            ->where('userid', $moodleUser->id)
            ->get();

        $submissionMap = $submissions->keyBy('assignment');

        $textSubmissions = DB::table('mdl_assignsubmission_onlinetext')
            ->whereIn('submission', $submissions->pluck('id')->toArray())
            ->get()
            ->keyBy('submission');

        foreach ($assignments as $assignment) {
            $assignment->status = 0;
            $assignment->text = '';

            if (isset($submissionMap[$assignment->id])) {
                $submission = $submissionMap[$assignment->id];
                $assignment->status = $submission->status === 'submitted' ? 1 : 0;
                $assignment->text = data_get($textSubmissions, $submission->id . '.onlinetext', '');
            }
        }

        return ['error' => false, 'data' => $assignments];
    }

    public function getCourseQuizzes(Request $request, $courseId)
    {
        $user = $request->user();
        $moodleUser = User::where('username', $user->username)->first();

        if (!$moodleUser) {
            return ['error' => true, 'message' => 'User not found in Moodle'];
        }

        $moduleId = DB::table('mdl_modules')->where('name', 'quiz')->value('id');

        $quizzes = DB::table('mdl_quiz')
            ->join('mdl_course_modules', function ($join) use ($moduleId) {
                $join->on('mdl_quiz.id', '=', 'mdl_course_modules.instance')
                    ->where('mdl_course_modules.module', '=', $moduleId);
            })
            ->join('mdl_course_sections', 'mdl_course_modules.section', '=', 'mdl_course_sections.id')
            ->where('mdl_quiz.course', $courseId)
            ->select(
                'mdl_quiz.id',
                'mdl_quiz.name',
                'mdl_quiz.course as course_id',
                DB::raw("DATE_FORMAT(FROM_UNIXTIME(mdl_quiz.timeopen), '%d/%m/%Y') as open_time"),
                DB::raw("DATE_FORMAT(FROM_UNIXTIME(mdl_quiz.timeclose), '%d/%m/%Y') as close_time"),
                'mdl_course_modules.section',
                'mdl_course_sections.name as section_name'
            )
            ->get();

        $quizIds = $quizzes->pluck('id')->toArray();

        $attempts = DB::table('mdl_quiz_attempts')
            ->whereIn('quiz', $quizIds)
            ->where('userid', $moodleUser->id)
            ->select('quiz', 'state')
            ->get();

        $attemptMap = [];
        foreach ($attempts as $attempt) {
            $attemptMap[$attempt->quiz] = $attempt->state === 'finished' ? 1 : 0;
        }

        foreach ($quizzes as $quiz) {
            $quiz->status = $attemptMap[$quiz->id] ?? 0;
        }

        return ['error' => false, 'data' => $quizzes];
    }
    public function getQuizQuestionsDetail($id)
    {
        // Kiểm tra quiz tồn tại
        $quiz = DB::table('mdl_quiz')->where('id', $id)->first();
    
        if (!$quiz) {
            return null;
        }
    
        // Lấy danh sách câu hỏi và đáp án từ quiz
        $rawQuestions = DB::table('mdl_quiz_slots')
            ->join('mdl_question as q', 'mdl_quiz_slots.questionid', '=', 'q.id')
            ->leftJoin('mdl_question_answers as a', 'a.question', '=', 'q.id')
            ->where('mdl_quiz_slots.quizid', $id)
            ->select(
                'mdl_quiz_slots.slot',
                'q.id as question_id',
                'q.name as questionname',
                'q.qtype',
                'q.questiontext',
                'q.defaultmark',
                'a.id as answer_id',
                'a.answer',
                'a.fraction'
            )
            ->orderBy('mdl_quiz_slots.slot')
            ->get();
    
        // Nhóm câu hỏi theo slot
        $grouped = $rawQuestions->groupBy('slot');
    
        $questions = [];
    
        foreach ($grouped as $slot => $items) {
            $first = $items->first();
            $answers = $items->filter(fn($ans) => $ans->answer_id !== null)->map(function ($ans) {
                return [
                    'answer_id' => $ans->answer_id,
                    'answer' => $ans->answer,
                    'is_correct' => floatval($ans->fraction) === 1.0
                ];
            })->values();
    
            $questions[] = [
                'slot' => $slot,
                'question_id' => $first->question_id,
                'questionname' => $first->questionname,
                'qtype' => $first->qtype,
                'questiontext' => $first->questiontext,
                'defaultmark' => $first->defaultmark,
                'answers' => $answers
            ];
        }
    
        return [
            'quiz' => [
                'id' => $quiz->id,
                'name' => $quiz->name
            ],
            'questions' => $questions
        ];
    }
    public function getAssignmentDetail($id, $userId)
    {
        // Get assignment details
        $assignment = DB::table('mdl_assign')
            ->join('mdl_course_modules', 'mdl_assign.id', '=', 'mdl_course_modules.instance')
            ->join('mdl_course_sections', 'mdl_course_modules.section', '=', 'mdl_course_sections.id')
            ->where('mdl_assign.id', $id)
            ->select(
                'mdl_assign.id',
                'mdl_assign.name',
                'mdl_assign.course',
                DB::raw("DATE_FORMAT(FROM_UNIXTIME(mdl_assign.duedate), '%d/%m/%Y') as duedate"),
                'mdl_course_modules.section',
                'mdl_course_sections.name as section_name',
                'mdl_course_modules.id as cmid'
            )
            ->first();

        if (!$assignment) {
            return null; // Return null if assignment is not found
        }

        // Get submission details
        $submission = DB::table('mdl_assign_submission')
            ->where('assignment', $id)
            ->where('userid', $userId)
            ->first();

        $status = 0;
        $onlinetext = '';
        $studentFiles = [];
        if ($submission) {
            $status = $submission->status === 'submitted' ? 1 : 0;

            // Get online text submission
            $textSubmission = DB::table('mdl_assignsubmission_onlinetext')
                ->where('submission', $submission->id)
                ->first();
            $onlinetext = $textSubmission->onlinetext ?? '';

            // Get student files
            $studentFiles = DB::table('mdl_files')
                ->where('component', 'assignsubmission_file')
                ->where('filearea', 'submission_files')
                ->where('itemid', $submission->id)
                ->where('filesize', '>', 0)
                ->select('filename', 'filepath', 'contenthash')
                ->get();
        }

        // ✅ Get contextid for the assignment module
        $context = DB::table('mdl_context')
            ->where('contextlevel', 70) // CONTEXT_MODULE
            ->where('instanceid', $assignment->cmid)
            ->first();

        // Get teacher files (instruction files)
        $teacherFiles = collect();
        if ($context) {
            $teacherFiles = DB::table('mdl_files')
                ->where('component', 'mod_assign')
                ->where('filearea', 'intro')
                ->where('itemid', $assignment->id)
                ->where('contextid', $context->id)
                ->where('filesize', '>', 0)
                ->select('filename', 'filepath', 'contenthash')
                ->get();
        }
        return [
            'assignment' => $assignment,
            'status' => $status,
            'onlinetext' => $onlinetext,
            'student_files' => $studentFiles,
            'teacher_files' => $teacherFiles,
        ];
    }

}
