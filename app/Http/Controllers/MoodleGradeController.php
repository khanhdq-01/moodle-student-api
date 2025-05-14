<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MoodleGradeService;

class MoodleGradeController extends Controller
{
    protected $service;

    public function __construct(MoodleGradeService $service)
    {
        $this->service = $service;
    }

    public function getAssignmentGrades(Request $request)
    {
        $userId = $request->user()->id;
        $grades = $this->service->getAssignmentGrades($userId);
        return response()->json($grades);
    }

    public function getQuizGrades(Request $request)
    {
        $userId = $request->user()->id;
        $grades = $this->service->getQuizGrades($userId);
        return response()->json($grades);
    }
}