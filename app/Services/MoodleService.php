<?php

namespace App\Services;

use App\Interfaces\MoodleRepositoryInterface;
use Illuminate\Http\Request;

class MoodleService
{
    protected $moodleRepository;

    public function __construct(MoodleRepositoryInterface $moodleRepository)
    {
        $this->moodleRepository = $moodleRepository;
    }

    public function getAssignmentsAndQuestions($user)
    {
        return $this->moodleRepository->getAssignmentsAndQuestions($user);
    }
    public function getFile($contenthash)
    {
        return $this->moodleRepository->getFile($contenthash);
    }
    public function submitAssignment(Request $request)
    {
        return $this->moodleRepository->submitAssignment($request);
    }
}
