<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;


class ForumController extends Controller
{
    public function getForums()
    {
        // Lấy ID người dùng từ phiên làm việc (auth)
        $user_id = Auth::id(); // Hoặc sử dụng JWT nếu không sử dụng Auth mặc định
    
        if (!$user_id) {
            // Trả về lỗi nếu người dùng chưa đăng nhập
            return response()->json(['message' => 'User not authenticated'], 401);
        }
    
        // Truy vấn để lấy danh sách các diễn đàn mà người dùng tham gia
        $forums = DB::table('mdl_forum as f')
            ->join('mdl_course as course', 'course.id', '=', 'f.course') // Kết nối với khóa học
            ->join('mdl_context as c', function ($join) {
                $join->on('c.instanceid', '=', 'course.id') // Kết nối với context khóa học
                     ->where('c.contextlevel', '=', 50);  // Context level 50 là khóa học
            })
            ->join('mdl_role_assignments as ra', 'ra.contextid', '=', 'c.id') // Kết nối với phân quyền
            ->join('mdl_user as u', 'u.id', '=', 'ra.userid') // Kết nối với người dùng
            ->where('u.id', $user_id) // Lọc theo ID người dùng
            ->where('ra.roleid', '>', 0) // Đảm bảo người dùng có quyền trong diễn đàn (không phải khách)
            ->select('f.id', 'f.name', 'f.intro', 'f.duedate', 'f.cutoffdate', 'f.timemodified')
            ->distinct() // Lọc để tránh trùng lặp diễn đàn
            ->get(); // Lấy dữ liệu
    
        // Trả về danh sách các diễn đàn
        return response()->json($forums);
    }
    

    public function getDiscussions($forum_id)
    {
        $user_id = Auth::id(); // Hoặc sử dụng JWT nếu không sử dụng Auth mặc định
    
        if (!$user_id) {
            // Trả về lỗi nếu người dùng chưa đăng nhập
            return response()->json(['message' => 'User not authenticated'], 401);
        }
        // Lấy tất cả bài thảo luận trong diễn đàn theo forum_id
        $discussions = DB::table('mdl_forum_discussions as d')
            ->join('mdl_user as u', 'u.id', '=', 'd.userid') // Lấy thông tin người tạo bài thảo luận
            ->join('mdl_forum_posts as p', 'p.discussion', '=', 'd.id') // Kết nối bài viết với cuộc thảo luận (sử dụng p.discussion để kết nối với d.id)
            ->where('d.forum', $forum_id) // Chỉ lấy bài thảo luận trong diễn đàn có forum_id
            ->select(
                'd.id',
                'd.name as name',
                'd.timemodified as last_modified',
                'd.userid as userid',
                'u.firstname',
                'u.lastname',
                'p.id as post_id',
                'p.message as content',
                'p.subject',  // Tiêu đề bài viết
                'p.created as post_created', // Thời gian tạo bài viết
                'p.modified as post_modified', // Thời gian chỉnh sửa bài viết
                'p.attachment', // File đính kèm
                'p.wordcount' // Số từ trong bài viết
            )
            ->orderBy('p.created', 'asc') // Sắp xếp bài viết theo thời gian tạo từ cũ đến mới
            ->get();
    
        // Trả về danh sách các bài thảo luận
        return response()->json([
            'forum_id' => $forum_id,
            'discussions' => $discussions
        ]);
    }
    
    public function createDiscussion(Request $request, $forum_id)
    {
        // Kiểm tra người dùng đã đăng nhập chưa
        $user = auth()->user();  // Lấy người dùng đã đăng nhập
        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }
    
        // Kiểm tra người dùng có tồn tại trong Moodle không
        $moodleUser = User::where('username', $user->username)->first();
        if (!$moodleUser) {
            return response()->json(['message' => 'User not found in Moodle'], 404);
        }
    
        // Validate dữ liệu đầu vào
        $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
        ]);
    
        // Bắt đầu transaction để đảm bảo dữ liệu được ghi đúng cách
        DB::beginTransaction();
    
        try {
            // Tạo chủ đề thảo luận trước (mdl_forum_discussions)
            $discussion_id = DB::table('mdl_forum_discussions')->insertGetId([
                'forum' => $forum_id,  // Liên kết với bảng mdl_forum
                'name' => $request->name,
                'userid' => $moodleUser->id,
                'timemodified' => now()->timestamp,
            ]);

            // Sau đó, tạo bài viết đầu tiên liên kết với discussion_id (mdl_forum_posts)
            $post_id = DB::table('mdl_forum_posts')->insertGetId([
                'discussion' => $discussion_id,  // Liên kết với discussion vừa tạo
                'userid' => $moodleUser->id,
                'subject' => $request->name,
                'message' => $request->content,
                'created' => now()->timestamp,
                'modified' => now()->timestamp,
            ]);
            // Commit transaction
            DB::commit();
    
            return response()->json([
                'message' => 'Discussion created successfully',
                'discussion_id' => $discussion_id,
                'post_id' => $post_id
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Failed to create discussion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getDiscussionDetails($forum_id, $discussion_id)
    {
        $discussion = DB::table('mdl_forum_discussions as d')
            ->join('mdl_user as u', 'u.id', '=', 'd.userid') // Lấy thông tin người tạo bài thảo luận
            ->where('d.id', $discussion_id) // Lọc theo ID bài thảo luận
            ->where('d.forum', $forum_id) // Lọc theo ID diễn đàn
            ->select(
                'd.id',
                'd.name',
                'd.timemodified as last_modified',
                'd.userid as author_id',
                'u.firstname',
                'u.lastname',
                'u.email',
                'd.forum',
                'd.timemodified'
            )
            ->first(); // Dùng first() thay vì get() để lấy 1 dòng
    
        // Kiểm tra nếu không tìm thấy bài thảo luận
        if (!$discussion) {
            return response()->json(['message' => 'Discussion not found'], 404);
        }

        $comments = DB::table('mdl_forum_posts as p')
            ->join('mdl_user as u', 'u.id', '=', 'p.userid') // Lấy thông tin người bình luận
            ->where('p.discussion', $discussion_id)
            ->where('p.parent', '>', 0)
            ->select(
                'p.id',
                'p.parent',
                'p.userid as author_id',
                'u.firstname',
                'u.lastname',
                'p.message as content',
                'p.subject',
                'p.created as comment_created',
                'p.modified as comment_modified'
            )
            ->orderBy('p.created', 'asc')
            ->get();
    
        return response()->json([
            'forum_id' => $forum_id,
            'discussion' => $discussion,
            'comments' => $comments
        ]);
    }
    
    public function postComment($forum_id, $discussion_id, Request $request)
    {
        $user_id = Auth::id();
    
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'parent_id' => 'nullable|integer' // ID của bài viết cha (nếu có)
        ]);
    
        $current_time = Carbon::now()->timestamp;
    
        // Lấy bài viết gốc của thảo luận
        $discussion_post = DB::table('mdl_forum_discussions')
        ->where('id', $discussion_id)
        ->where('forum', $forum_id) // Kiểm tra bài thảo luận có thuộc diễn đàn không
        ->exists();

    if (!$discussion_post) {
        return response()->json(['message' => 'Discussion not found'], 404);
    }
    
        // Xác định parent: Nếu không có parent_id từ request, lấy bài viết gốc
        $parent_id = $validated['parent_id'] ?? $discussion_post;
    
        // Chèn bình luận vào database
        $post_id = DB::table('mdl_forum_posts')->insertGetId([
            'discussion' => $discussion_id,
            'parent' => $parent_id, // Trỏ về bài viết gốc hoặc bình luận cha
            'userid' => $user_id,
            'message' => $validated['message'],
            'created' => $current_time,
            'modified' => $current_time,
            'subject' => 'Bình luận mới',
            'attachment' => '',
            'wordcount' => str_word_count($validated['message']),
        ]);
    
        return response()->json([
            'message' => 'Comment posted successfully',
            'post_id' => $post_id,
            'parent_id' => $parent_id,
            'content' => $validated['message'],
            'created_at' => Carbon::createFromTimestamp($current_time)->toDateTimeString(),
        ], 201);
    }
    
    
}
