<?php 
namespace App\Services;

use App\Interfaces\QuizInterface;

class QuizService
{
    protected $quizRepository;

    public function __construct(QuizInterface $quizRepository)
    {
        $this->quizRepository = $quizRepository;
    }

    public function startAttempt(int $quizId, int $userId)
    {
        return $this->quizRepository->startAttempt($quizId, $userId);
    }

    public function submitAnswer(int $quizId, int $userId, array $data)
    {
        return $this->quizRepository->submitAnswer($quizId, $userId, $data);
    }

    public function finishAttempt(int $quizId, int $userId, int $attemptId)
    {
        return $this->quizRepository->finishAttempt($quizId, $userId, $attemptId);
    }

    public function getResult(int $quizId, int $userId)
    {
        return $this->quizRepository->getResult($quizId, $userId);
    }
}
