<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class MoodleGradeController extends Controller
{
    public function getStudentGrades(Request $request)
    {
        // Lấy ID của sinh viên đang đăng nhập
        $user = $request->user();
        $userId = $user->id;

        // Truy vấn điểm, trạng thái và hạn nộp bài
        $grades = DB::select("
            SELECT 
                gi.itemname AS ten_bai_tap,
                g.finalgrade AS diem_he_10,
                (g.finalgrade / 10) * 4 AS diem_he_4,
                s.status AS trang_thai,
                a.duedate AS han_nop_bai
            FROM mdl_grade_grades g
            JOIN mdl_grade_items gi ON g.itemid = gi.id
            LEFT JOIN mdl_assign_submission s ON s.userid = g.userid
            LEFT JOIN mdl_assign a ON a.id = s.assignment
            WHERE g.userid = ?
        ", [$userId]);

        return response()->json($grades);
    }
}