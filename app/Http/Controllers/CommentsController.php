<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommentsController extends Controller
{
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'video_id' => 'required|exists:videos,id',
            'comment' => 'required|string',
            'parent_id' => 'nullable|exists:comments,id',
        ])->stopOnFirstFailure();;

        if ($validator->fails()) {
            // Get the first error message
            $firstError = $validator->errors()->first();
            return response()->json(['error' => $firstError], 422);
        }

        $comment = Comment::create([
            'video_id' => $request->video_id,
            'user_id' => auth()->id(),
            'comment' => $request->comment,
            'parent_id' => $request->parent_id,
        ]);

        return response()->json(['message' => 'Comment added successfully', 'comment' => $comment]);
    }

    public function index($video_id)
    {
        $video = Video::with(['comments' => function ($query) {
            $query->whereNull('parent_id')->with('replies.user.userMeta', 'reactions', 'user.userMeta');
        }])->findOrFail($video_id);

        $comments = $video->comments->map(function ($comment) {
            return [
                'id' => $comment->id,
                'comment' => $comment->comment,
                'user_id' => $comment->user_id,
                'userMeta' => [
                    'name' => $comment->user->userMeta->name,
                    'country_code' => $comment->user->userMeta->country_code,
                    'phone_number' => $comment->user->userMeta->phone_number,
                    'cover_image' => $comment->user->userMeta->cover_image ? asset('public/' . $comment->user->userMeta->cover_image) : null,
                    'profile_image' => $comment->user->userMeta->profile_image ? asset('public/' . $comment->user->userMeta->profile_image) : null,
                    'created_at' => $comment->user->userMeta->created_at,
                ],
                'replies' => $comment->replies->map(function ($reply) {
                    return [
                        'id' => $reply->id,
                        'comment' => $reply->comment,
                        'user_id' => $reply->user_id,
                        'userMeta' => [
                            'name' => $reply->user->userMeta->name,
                            'country_code' => $reply->user->userMeta->country_code,
                            'phone_number' => $reply->user->userMeta->phone_number,
                            'cover_image' => $reply->user->userMeta->cover_image ? asset('public' . $reply->user->userMeta->cover_image) : null,
                            'profile_image' => $reply->user->userMeta->profile_image ? asset('public' . $reply->user->userMeta->profile_image) : null,
                            'created_at' => $reply->user->userMeta->created_at,
                        ],
                    ];
                }),
                'reactionCount' => $comment->reactions->count(),
            ];
        });

        return response()->json([
            "message" => "Comments retrieved successfully",
            "data" => $comments
        ]);
    }

    public function show($id)
    {
        $comment = Comment::with('user', 'reactions')->find($id);

        if (!$comment) {
            return response()->json(['message' => 'Comment not found'], 404);
        }

        return response()->json($comment);
    }

    public function destroy($id)
    {
        $comment = Comment::with('user', 'reactions')->find($id);
        if (!$comment) {
            return response()->json([
                "message" => "Comment not found",
            ], 422);
        }
        $comment->delete();
        return response()->json([
            "message" => "Comment deleted successfully",
        ], 201);
    }
}
