<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use App\Interfaces\MoodleRepositoryInterface;

class MoodleRepository implements MoodleRepositoryInterface
{
    public function getAssignmentsAndQuestions($user)
    {
        $moodleUser = User::where('username', $user->username)->first();
        if (!$moodleUser) {
            return ['status' => 404, 'data' => ['message' => 'User not found in Moodle']];
        }

        $courses = DB::table('mdl_user_enrolments')
            ->join('mdl_enrol', 'mdl_user_enrolments.enrolid', '=', 'mdl_enrol.id')
            ->where('mdl_user_enrolments.userid', $moodleUser->id)
            ->pluck('mdl_enrol.courseid')
            ->toArray();

        if (empty($courses)) {
            return [
                'status' => 200,
                'data' => [
                    'message' => 'User chưa ghi danh vào khóa học nào',
                    'assignments' => [],
                    'questions' => []
                ]
            ];
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
                'mdl_course_modules.section'
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
                'url' => URL::to("/moodledata/{$file->contenthash}"),
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

        return [
            'status' => 200,
            'data' => [
                'message' => 'Danh sách bài tập và câu hỏi của user',
                'assignments' => $assignments,
                'questions' => $questions
            ]
        ];
    }
    public function getFile($contenthash)
    {
        $moodleDataPath = 'C:/xampp/moodledata/filedir';

        $filePath = "{$moodleDataPath}/" . substr($contenthash, 0, 2) . "/" . substr($contenthash, 2, 2) . "/{$contenthash}";

        if (!file_exists($filePath)) {
            return response()->json(['message' => 'File not found in Moodle storage'], 404);
        }

        $file = DB::table('mdl_files')->where('contenthash', $contenthash)->first();
        $mimeType = $file->mimetype ?? 'application/octet-stream';
        $filename = $file->filename ?? 'downloaded_file';

        return response()->download($filePath, $filename, [
            'Content-Type' => $mimeType,
        ]);
    }
    public function submitAssignment(Request $request)
    {
        $user = $request->user();
        $moodleUser = User::where('username', $user->username)->first();
        if (!$moodleUser) {
            return response()->json(['message' => 'User not found in Moodle'], 404);
        }

        if (!$request->hasFile('file')) {
            return response()->json(['message' => 'Không có file để nộp bài'], 400);
        }

        $assignmentId = $request->assignment_id;
        $assignment = DB::table('mdl_assign')->where('id', $assignmentId)->first();
        if (!$assignment) {
            return response()->json(['message' => 'Assignment không tồn tại'], 404);
        }

        $existingSubmission = DB::table('mdl_assign_submission')
            ->where('assignment', $assignmentId)
            ->where('userid', $moodleUser->id)
            ->first();

        if ($existingSubmission) {
            DB::table('mdl_assign_submission')->where('id', $existingSubmission->id)
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
                return response()->json(['message' => 'Không thể lưu file vào Moodle storage'], 500);
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

        return response()->json([
            'code' => 200,
            'message' => 'Nộp bài thành công',
            'submission_id' => $submissionId,
            'files' => $savedFiles
        ]);
    }
}
