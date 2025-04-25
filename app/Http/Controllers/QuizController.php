<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\QuizService;

class QuizController extends Controller
{
    protected $quizService;

    public function __construct(QuizService $quizService)
    {
        $this->quizService = $quizService;
    }

    public function startAttempt(Request $request, $quizId)
    {
        $userId = $request->user()->id;
        return response()->json($this->quizService->startAttempt($quizId, $userId));
    }

    public function submitAnswer(Request $request, $quizId)
    {
        $data = $request->validate([
            'attempt_id'   => 'required|integer',
            'question_id'  => 'required|integer',
            'answer_ids'   => 'required|array|min:1',
            'answer_ids.*' => 'integer',
        ]);

        $userId = $request->user()->id;
        return response()->json($this->quizService->submitAnswer($quizId, $userId, $data));
    }

    public function finishAttempt(Request $request, $quizId)
    {
        $data = $request->validate(['attempt_id' => 'required|integer']);
        $userId = $request->user()->id;
        return response()->json($this->quizService->finishAttempt($quizId, $userId, $data['attempt_id']));
    }

    public function getResult(Request $request, $quizId)
    {
        $userId = $request->user()->id;
        return response()->json($this->quizService->getResult($quizId, $userId));
    }
}
