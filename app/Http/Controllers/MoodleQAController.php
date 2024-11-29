<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;

class MoodleQAController extends Controller
{
    public function getQuestions(Request $request)
    {
        $user = $request->user();

        $questions = DB::select("
            SELECT 
                d.id AS question_id,
                d.name AS question_title,
                CASE 
                    WHEN (SELECT COUNT(*) FROM mdl_forum_posts p WHERE p.discussion = d.id AND p.parent != 0) > 0 
                    THEN 'Đã trả lời' 
                    ELSE 'Đang chờ' 
                END AS status
            FROM mdl_forum_discussions d
            ORDER BY d.timemodified DESC;
        ");

        return response()->json($questions);
    }
}
