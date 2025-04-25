<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MoodleQAService;

class MoodleQAController extends Controller
{
    protected $service;

    public function __construct(MoodleQAService $service)
    {
        $this->service = $service;
    }

    public function getQuestions(Request $request)
    {
        $questions = $this->service->getQuestions();
        return response()->json($questions);
    }
}
