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
            ->where('u.id', $user_id)
            ->where('ra.roleid', '>', 0)
            ->select('f.id', 'f.name', 'f.intro', 'f.duedate', 'f.cutoffdate', 'f.timemodified')
            ->distinct()
            ->get();
    }

    public function getDiscussions($forum_id)
    {
        return DB::table('mdl_forum_discussions as d')
            ->join('mdl_user as u', 'u.id', '=', 'd.userid')
            ->join('mdl_forum_posts as p', 'p.discussion', '=', 'd.id')
            ->where('d.forum', $forum_id)
            ->select(
                'd.id', 'd.name as name', 'd.timemodified as last_modified',
                'd.userid as userid', 'u.firstname', 'u.lastname',
                'p.id as post_id', 'p.message as content', 'p.subject',
                'p.created as post_created', 'p.modified as post_modified',
                'p.attachment', 'p.wordcount'
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
            ->select('d.id', 'd.name', 'd.timemodified as last_modified',
                     'd.userid as author_id', 'u.firstname', 'u.lastname',
                     'u.email', 'd.forum', 'd.timemodified')
            ->first();

        $comments = DB::table('mdl_forum_posts as p')
            ->join('mdl_user as u', 'u.id', '=', 'p.userid')
            ->where('p.discussion', $discussion_id)
            ->where('p.parent', '>', 0)
            ->select('p.id', 'p.parent', 'p.userid as author_id',
                     'u.firstname', 'u.lastname', 'p.message as content',
                     'p.subject', 'p.created as comment_created',
                     'p.modified as comment_modified')
            ->orderBy('p.created', 'asc')
            ->get();

        return compact('discussion', 'comments');
    }

    public function postComment($forum_id, $discussion_id, $user_id, array $data)
    {
        $exists = DB::table('mdl_forum_discussions')
            ->where('id', $discussion_id)
            ->where('forum', $forum_id)
            ->exists();

        if (!$exists) return null;

        $parent_id = $data['parent_id'] ?? 0;

        $post_id = DB::table('mdl_forum_posts')->insertGetId([
            'discussion' => $discussion_id,
            'parent' => $parent_id,
            'userid' => $user_id,
            'message' => $data['message'],
            'created' => Carbon::now()->timestamp,
            'modified' => Carbon::now()->timestamp,
            'subject' => 'Bình luận mới',
            'attachment' => '',
            'wordcount' => str_word_count($data['message']),
        ]);

        return $post_id;
    }
}
