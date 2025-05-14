<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ForumService;
use Illuminate\Support\Facades\Auth;

class ForumController extends Controller
{
    protected $forumService;

    public function __construct(ForumService $forumService)
    {
        $this->forumService = $forumService;
    }

    public function getForums()
    {
        $user_id = Auth::id();
        if (!$user_id) return response()->json(['message' => 'Unauthenticated'], 401);

        return response()->json($this->forumService->getForums($user_id));
    }

    public function getDiscussions($forum_id)
    {
        return response()->json([
            'forum_id' => $forum_id,
            'discussions' => $this->forumService->getDiscussions($forum_id)
        ]);
    }

    public function createDiscussion(Request $request, $forum_id)
    {
        $user = auth()->user();
        if (!$user) return response()->json(['message' => 'Unauthenticated'], 401);

        $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $result = $this->forumService->createDiscussion(
            $forum_id, $user->id, $request->name, $request->content
        );

        return response()->json([
            'message' => 'Created',
            'discussion_id' => $result['discussion_id'],
            'post_id' => $result['post_id']
        ], 201);
    }

    public function getDiscussionDetails($forum_id, $discussion_id)
    {
        $data = $this->forumService->getDiscussionDetails($forum_id, $discussion_id);

        if (!$data['discussion']) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json([
            'forum_id' => $forum_id,
            'discussion' => $data['discussion'],
            'comments' => $data['comments']
        ]);
    }

    public function postComment($forum_id, $discussion_id, Request $request)
    {
        $user_id = Auth::id();
        if (!$user_id) return response()->json(['message' => 'Unauthenticated'], 401);

        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'parent_id' => 'nullable|integer',
            'subject' => 'nullable|string|max:255',
        ]);


        $post_id = $this->forumService->postComment(
            $forum_id, $discussion_id, $user_id, $validated
        );

        if (!$post_id) return response()->json(['message' => 'Discussion not found'], 404);

        return response()->json([
            'message' => 'Comment posted',
            'post_id' => $post_id
        ], 201);
    }

    public function deleteComment($forum_id, $discussion_id, $comment_id)
    {
        $user_id = Auth::id();
        if (!$user_id) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $deleted = $this->forumService->deleteComment($forum_id, $discussion_id, $comment_id, $user_id);

        if (!$deleted) {
            return response()->json(['message' => 'Comment not found or unauthorized'], 404);
        }

        return response()->json(['message' => 'Comment deleted successfully']);
    }
}
