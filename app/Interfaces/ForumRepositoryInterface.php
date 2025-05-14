<?php

namespace App\Interfaces;

interface ForumRepositoryInterface
{
    public function getForumsByUser($user_id);
    public function getDiscussions($forum_id);
    public function createDiscussion($forum_id, $user_id, $name, $content);
    public function getDiscussionDetails($forum_id, $discussion_id);
    public function postComment($forum_id, $discussion_id, $user_id, array $data);
    public function deleteComment(int $forum_id, int $discussion_id, int $comment_id, int $user_id): bool;
}
