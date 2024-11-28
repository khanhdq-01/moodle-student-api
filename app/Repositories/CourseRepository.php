<?php

namespace App\Repositories;

use App\Models\Course;
use App\Interfaces\CourseRepositoryInterface;

class CourseRepository implements CourseRepositoryInterface
{
    public function getAllCourses()
    {
        return $test = Course::with(['teachers', 'assistants', 'students', 'modules', 'customFields'])->get();
        dd($test);
    }

    public function getCoursesByStudentId($studentId)
    {
        return Course::whereHas('students', function ($query) use ($studentId) {
            $query->where('userid', $studentId);
        })->with(['teachers', 'assistants', 'modules', 'customFields'])->get();
    }

    public function getAvailableCoursesForStudent($studentId)
    {
        return Course::whereDoesntHave('students', function ($query) use ($studentId) {
            $query->where('userid', $studentId);
        })
            ->with(['teachers', 'assistants', 'modules'])
            ->get();
    }
}
