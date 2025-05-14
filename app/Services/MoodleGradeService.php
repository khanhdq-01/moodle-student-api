<?php
namespace App\Services;

use App\Interfaces\MoodleGradeRepositoryInterface;

class MoodleGradeService
{
    protected $repository;

    public function __construct(MoodleGradeRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getAssignmentGrades(int $userId)
    {
        return $this->repository->getAssignmentGrades($userId);
    }

    public function getQuizGrades(int $userId)
    {
        return $this->repository->getQuizGrades($userId);
    }
}
