<?php 
namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Interfaces\MoodleGradeRepositoryInterface;

class MoodleGradeRepository implements MoodleGradeRepositoryInterface
{
    public function getAssignmentGrades(int $userId)
    {
        return DB::select("
            SELECT 
                a.id AS assignment_id,
                gi.itemname AS ten_bai_tap,
                g.finalgrade AS diem_he_10,
                (g.finalgrade / 10) * 4 AS diem_he_4,
                s.status AS trang_thai,
                a.duedate AS han_nop_bai
            FROM mdl_grade_grades g
            JOIN mdl_grade_items gi ON g.itemid = gi.id AND gi.itemmodule = 'assign'
            LEFT JOIN mdl_assign_submission s ON s.userid = g.userid AND s.assignment = gi.iteminstance
            LEFT JOIN mdl_assign a ON a.id = gi.iteminstance
            WHERE g.userid = ?
        ", [$userId]);
    }

    public function getQuizGrades(int $userId)
    {
        return DB::select("
            SELECT 
                q.id AS quiz_id,
                gi.itemname AS ten_bai_kiem_tra,
                g.finalgrade AS diem_he_10,
                (g.finalgrade / 10) * 4 AS diem_he_4,
                qa.state AS trang_thai_lam_bai,
                q.timeclose AS han_nop_bai
            FROM mdl_grade_grades g
            JOIN mdl_grade_items gi ON g.itemid = gi.id AND gi.itemmodule = 'quiz'
            LEFT JOIN mdl_quiz_attempts qa ON qa.userid = g.userid AND qa.quiz = gi.iteminstance
            LEFT JOIN mdl_quiz q ON q.id = gi.iteminstance
            WHERE g.userid = ?
        ", [$userId]);
    }
}
