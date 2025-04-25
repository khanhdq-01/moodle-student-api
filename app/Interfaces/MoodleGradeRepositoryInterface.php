<?php 
namespace App\Interfaces;

interface MoodleGradeRepositoryInterface
{
    public function getAssignmentGrades(int $userId);
    public function getQuizGrades(int $userId);
}
