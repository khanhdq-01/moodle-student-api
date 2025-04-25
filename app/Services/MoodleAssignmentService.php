<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Interfaces\MoodleAssignmentInterface;

class MoodleAssignmentService
{
    protected $repository;

    public function __construct(MoodleAssignmentInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getAssignmentsAndQuestions($user)
    {
        return $this->repository->getAssignmentsAndQuestions($user);
    }

    public function getFile($contenthash)
    {
        return $this->repository->getFile($contenthash);
    }

    public function uploadFile($file, $assignmentId, $userId)
    {
        return $this->repository->uploadFile($file, $assignmentId, $userId);
    }

    public function submitAssignment(Request $request)
    {
        return $this->repository->submitAssignment($request);
    }

    public function getUserAssignments(Request $request)
    {
        return $this->repository->getUserAssignments($request);
    }
    public function getUserQuizzes(Request $request)
    {
        return $this->repository->getUserQuizzes($request);
    }
    public function getCourseAssignments($courseId, Request $request)
    {
        return $this->repository->getCourseAssignments($courseId, $request);
    }

    public function getCourseQuizzes(Request $request, $courseId)
    {
        return $this->repository->getCourseQuizzes($request, $courseId);
    }
    public function getQuizQuestionsDetail($id)
    {
        return $this->repository->getQuizQuestionsDetail($id);
    }
    public function getAssignmentsDetail($id, $userId)
    {
        return $this->repository->getAssignmentDetail($id, $userId);
    }
}
