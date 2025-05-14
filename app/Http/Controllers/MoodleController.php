<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MoodleService;

class MoodleController extends Controller
{
    protected $moodleService;

    public function __construct(MoodleService $moodleService)
    {
        $this->moodleService = $moodleService;
    }

    public function getAssignmentsAndQuestions(Request $request)
    {
        $result = $this->moodleService->getAssignmentsAndQuestions($request->user());

        return response()->json($result['data'], $result['status']);
    }
    public function getFile($contenthash)
    {
        return $this->moodleService->getFile($contenthash);
    }

    public function submitAssignment(Request $request)
    {
        return $this->moodleService->submitAssignment($request);
    }
}
