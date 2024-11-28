<?php

namespace App\Interfaces;

interface CourseRepositoryInterface
{
    public function getAllCourses();
    public function getCoursesByStudentId($studentId);
    public function getAvailableCoursesForStudent($studentId);
}
