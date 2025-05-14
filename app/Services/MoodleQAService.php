<?php
namespace App\Services;

use App\Interfaces\MoodleQARepositoryInterface;

class MoodleQAService
{
    protected $repository;

    public function __construct(MoodleQARepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getQuestions()
    {
        return $this->repository->getQuestions();
    }
}
