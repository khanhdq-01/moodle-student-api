<?php

namespace App\Services;

use App\Interfaces\ForumRepositoryInterface;

class ForumService
{
    protected $forumRepo;

    public function __construct(ForumRepositoryInterface $forumRepo)
    {
        $this->forumRepo = $forumRepo;
    }

    public function getForums($user_id)
    {
        return $this->forumRepo->getForumsByUser($user_id);
    }

    public function getDiscussions($forum_id)
    {
        return $this->forumRepo->getDiscussions($forum_id);
    }

    public function createDiscussion($forum_id, $user_id, $name, $content)
    {
        return $this->forumRepo->createDiscussion($forum_id, $user_id, $name, $content);
    }

    public function getDiscussionDetails($forum_id, $discussion_id)
    {
        return $this->forumRepo->getDiscussionDetails($forum_id, $discussion_id);
    }

    public function postComment($forum_id, $discussion_id, $user_id, $data)
    {
        return $this->forumRepo->postComment($forum_id, $discussion_id, $user_id, $data);
    }
}
