<?php

namespace App\Interfaces;
use Illuminate\Http\Request;

interface MoodleRepositoryInterface
{
    public function getAssignmentsAndQuestions($user);
    public function getFile($contenthash);
    public function submitAssignment(Request $request);
}