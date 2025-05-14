<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Interfaces\ForumRepositoryInterface;

class ForumRepository implements ForumRepositoryInterface
{
    public function getForumsByUser($user_id)
    {
        return DB::table('mdl_forum as f')
            ->join('mdl_course as course', 'course.id', '=', 'f.course')
            ->join('mdl_context as c', function ($join) {
                $join->on('c.instanceid', '=', 'course.id')
                    ->where('c.contextlevel', '=', 50);
            })
            ->join('mdl_role_assignments as ra', 'ra.contextid', '=', 'c.id')
            ->join('mdl_user as u', 'u.id', '=', 'ra.userid')
            ->leftJoin('mdl_forum_discussions as d', 'd.forum', '=', 'f.id')
            ->leftJoin('mdl_user as creator', 'creator.id', '=', 'd.userid')
            ->where('u.id', $user_id)
            ->where('ra.roleid', '>', 0)
            ->select(
                'f.id as forum_id',
                'f.name as forum_name',
                'f.intro',
                'f.duedate',
                'f.cutoffdate',
                'f.timemodified',
                'd.userid as creator_id',
                'creator.firstname as creator_firstname',
                'creator.lastname as creator_lastname',
                'course.id as course_id',
                'course.fullname as course_name'
            )
            ->distinct()
            ->get();
    }

    public function getDiscussions($forum_id)
    {
        return DB::table('mdl_forum_discussions as d')
            ->join('mdl_user as u', 'u.id', '=', 'd.userid')
            ->join('mdl_forum_posts as p', function ($join) {
                $join->on('p.discussion', '=', 'd.id')
                    ->where('p.parent', '=', 0); // Chỉ lấy post gốc
            })
            ->leftJoin('mdl_user as um', 'um.id', '=', 'd.usermodified')
            ->leftJoin(DB::raw('(
                SELECT discussion, COUNT(*) as total_replies
                FROM mdl_forum_posts
                WHERE parent != 0
                GROUP BY discussion
            ) as c'), 'c.discussion', '=', 'd.id')
            ->where('d.forum', $forum_id)
            ->select(
                'd.id',
                'd.name as name',
                'd.timemodified as last_modified',
                'd.userid as userid',
                'u.firstname as user_firstname',
                'u.lastname as user_lastname',
                'd.usermodified as usermodified_id',
                'um.firstname as usermodified_firstname',
                'um.lastname as usermodified_lastname',
                'p.id as post_id',
                'p.message as content',
                'p.subject',
                'p.created as post_created',
                'p.modified as post_modified',
                DB::raw('COALESCE(c.total_replies, 0) as total_replies')
            )
            ->orderBy('p.created', 'asc')
            ->get();
    }

    public function createDiscussion($forum_id, $user_id, $name, $content)
    {
        return DB::transaction(function () use ($forum_id, $user_id, $name, $content) {
            $discussion_id = DB::table('mdl_forum_discussions')->insertGetId([
                'forum' => $forum_id,
                'name' => $name,
                'userid' => $user_id,
                'timemodified' => now()->timestamp,
            ]);

            $post_id = DB::table('mdl_forum_posts')->insertGetId([
                'discussion' => $discussion_id,
                'userid' => $user_id,
                'subject' => $name,
                'message' => $content,
                'created' => now()->timestamp,
                'modified' => now()->timestamp,
            ]);

            return compact('discussion_id', 'post_id');
        });
    }

    public function getDiscussionDetails($forum_id, $discussion_id)
    {
        $discussion = DB::table('mdl_forum_discussions as d')
            ->join('mdl_user as u', 'u.id', '=', 'd.userid')
            ->where('d.id', $discussion_id)
            ->where('d.forum', $forum_id)
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
            ->first();

        if (!$discussion) return null;

        $comments = DB::table('mdl_forum_posts as p')
            ->join('mdl_user as u', 'u.id', '=', 'p.userid')
            ->where('p.discussion', $discussion_id)
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

        return [
            'discussion' => $discussion,
            'comments' => $comments, // trả về đúng key
        ];
    }


    public function postComment($forum_id, $discussion_id, $user_id, array $data)
    {
        $exists = DB::table('mdl_forum_discussions')
            ->where('id', $discussion_id)
            ->where('forum', $forum_id)
            ->exists();

        if (!$exists) return null;

        $parent_id = $data['parent_id'] ?? 0;

        // Ưu tiên subject từ request, nếu không có thì lấy từ parent hoặc dùng mặc định
        $subject = $data['subject'] ?? null;

        if (!$subject) {
            if ($parent_id > 0) {
                $parentPost = DB::table('mdl_forum_posts')->where('id', $parent_id)->first();
                $subject = $parentPost ? $parentPost->subject : '(No subject)';
            } else {
                $subject = '(No subject)';
            }
        }
        $timestamp = Carbon::now()->timestamp;

        $post_id = DB::table('mdl_forum_posts')->insertGetId([
            'discussion' => $discussion_id,
            'parent' => $parent_id,
            'userid' => $user_id,
            'message' => $data['message'],
            'created' => $timestamp,
            'modified' => $timestamp,
            'subject' => $subject,
            'attachment' => '',
            'wordcount' => str_word_count(strip_tags($data['message'])),
            'mailnow' => 0
        ]);

        // Cập nhật discussion (không cập nhật 'lastpost' nếu cột không tồn tại)
        DB::table('mdl_forum_discussions')
            ->where('id', $discussion_id)
            ->update([
                'timemodified' => $timestamp,
                'usermodified' => $user_id
            ]);

        return $post_id;
    }

    public function deleteComment(int $forum_id, int $discussion_id, int $comment_id, int $user_id): bool
    {
        $comment = DB::table('mdl_forum_posts')
            ->where('id', $comment_id)
            ->where('discussion', $discussion_id)
            ->where('userid', $user_id)
            ->first();

        if (!$comment) {
            return false;
        }

        DB::table('mdl_forum_posts')->where('id', $comment_id)->delete();
        return true;
    }

}
