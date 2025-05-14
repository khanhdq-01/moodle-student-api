<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use App\Services\MoodleAssignmentService;

class MoodleAssignmentController extends Controller
{
    protected $assignmentService;

    public function __construct(MoodleAssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
    }

    public function getAssignmentsAndQuestions(Request $request)
    {
        $user = $request->user();
        $result = $this->assignmentService->getAssignmentsAndQuestions($user);

        if (isset($result['error']) && $result['error']) {
            return response()->json(['message' => $result['message']], 404);
        }

        return response()->json([
            'code' => 200,
            'message' => $result['message'],
            'data' => [
                'assignments' => $result['assignments'],
                'questions' => $result['questions']
            ]
        ]);
    }

    public function getFile($contenthash)
    {
        $result = $this->assignmentService->getFile($contenthash);

        if ($result['error']) {
            return response()->json(['message' => $result['message']], 404);
        }

        return Response::make(file_get_contents($result['file']), 200, [
            'Content-Type' => $result['mimeType'],
            'Content-Disposition' => 'attachment; filename="' . $result['filename'] . '"',
            'Content-Length' => $result['fileSize'],
        ]);
    }
    public function uploadFile(Request $request)
    {
        // Validate request
        $request->validate([
            'file' => 'required|file',
            'assignment_id' => 'required|integer',
        ]);

        $file = $request->file('file');
        $assignmentId = $request->input('assignment_id');
        $userId = auth()->id() ?? 0;

        $result = $this->assignmentService->uploadFile($file, $assignmentId, $userId);

        if ($result['error']) {
            return response()->json(['message' => $result['message']], 409);
        }

        return response()->json(['message' => $result['message']], 200);
    }
    
    public function submitAssignment(Request $request)
    {
        try {
            $result = $this->assignmentService->submitAssignment($request);

            return response()->json([
                'code' => 200,
                'message' => 'Nộp bài thành công',
                'submission_id' => $result['submission_id'],
                'files' => $result['files'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }
    public function getUserQuizzes(Request $request)
    {
        $result = $this->assignmentService->getUserQuizzes($request);

        if ($result['error']) {
            return response()->json(['message' => $result['message']], 404);
        }

        return response()->json([
            'code' => 200,
            'message' => 'Danh sách bài trắc nghiệm',
            'data' => $result['data']
        ]);
    }

    //lấy danh sách bài tập tự luận
    public function getUserAssignments(Request $request)
    {
        $response = $this->assignmentService->getUserAssignments($request);

        if (isset($response['error']) && $response['error']) {
            return response()->json(['message' => $response['message']], $response['status']);
        }

        return response()->json([
            'code' => $response['status'],
            'message' => $response['message'],
            'data' => $response['data']
        ]);
    }

    public function getCourseAssignments($courseId, Request $request)
    {
        $result = $this->assignmentService->getCourseAssignments($courseId, $request);

        if ($result['error']) {
            return response()->json(['message' => $result['message']], 404);
        }

        return response()->json([
            'code' => 200,
            'message' => 'Danh sách bài tự luận theo khóa học',
            'data' => $result['data']
        ]);
    }

    public function getCourseQuizzes(Request $request, $courseId)
    {
        $result = $this->assignmentService->getCourseQuizzes($request, $courseId);

        if ($result['error']) {
            return response()->json(['message' => $result['message']], 404);
        }

        return response()->json([
            'code' => 200,
            'message' => 'Danh sách bài trắc nghiệm theo khóa học',
            'data' => $result['data']
        ]);
    }
    public function getQuizQuestionsDetail($id)
    {
        $data = $this->assignmentService->getQuizQuestionsDetail($id);

        if (!$data) {
            return response()->json(['message' => 'Quiz not found'], 404);
        }

        return response()->json([
            'code' => 200,
            'message' => 'Danh sách câu hỏi theo bài trắc nghiệm',
            'quiz' => $data['quiz'],
            'questions' => $data['questions']
        ]);
    }

    public function getAssignmentsDetail($id, Request $request)
    {
        $user = $request->user();
        $moodleUser = User::where('username', $user->username)->first();
        if (!$moodleUser) {
            return response()->json(['message' => 'User not found in Moodle'], 404);
        }

        $data = $this->assignmentService->getAssignmentsDetail($id, $moodleUser->id);

        if (!$data) {
            return response()->json(['message' => 'Assignment not found'], 404);
        }

        return response()->json([
            'code' => 200,
            'message' => 'Chi tiết bài tự luận',
            'assignment' => [
                'id' => $data['assignment']->id,
                'name' => $data['assignment']->name,
                'course' => $data['assignment']->course,
                'duedate' => $data['assignment']->duedate,
                'section' => $data['assignment']->section,
                'section_name' => $data['assignment']->section_name,
                'status' => $data['status'],
                'onlinetext' => $data['onlinetext'],
                'student_files' => $data['student_files'],
                'teacher_files' => $data['teacher_files']
            ]
        ]);
    }
}
