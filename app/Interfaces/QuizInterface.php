<?php 
namespace App\Interfaces;
use Illuminate\Http\Request;

interface QuizInterface
{
    public function startAttempt(int $quizId, int $userId);
    public function submitAnswer(int $quizId, int $userId, array $data);
    public function finishAttempt(int $quizId, int $userId, int $attemptId);
    public function getResult(int $quizId, int $userId);
}
