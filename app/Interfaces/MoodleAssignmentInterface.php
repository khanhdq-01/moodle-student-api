<?php

namespace App\Interfaces;
use Illuminate\Http\Request;

interface MoodleAssignmentInterface
{
    public function getAssignmentsAndQuestions($user);
    public function getFile($contenthash);
    public function uploadFile($file, $assignmentId, $userId);
    public function submitAssignment(Request $request);
    public function getUserAssignments(Request $request);
    public function getUserQuizzes(Request $request);
    public function getCourseAssignments($courseId, Request $request);
    public function getCourseQuizzes(Request $request, $courseId);
    public function getQuizQuestionsDetail($id);
    public function getAssignmentDetail($id, $userId);
}