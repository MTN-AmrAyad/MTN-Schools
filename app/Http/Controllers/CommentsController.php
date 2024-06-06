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
                'video_id' => $comment->video_id,
                'created_at' => $comment->created_at,
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
                        'parent_id' => $reply->parent_id,
                        'created_at' => $reply->created_at,
                        'userMeta' => [
                            'name' => $reply->user->userMeta->name,
                            'country_code' => $reply->user->userMeta->country_code,
                            'phone_number' => $reply->user->userMeta->phone_number,
                            'cover_image' => $reply->user->userMeta->cover_image ? asset('public/' . $reply->user->userMeta->cover_image) : null,
                            'profile_image' => $reply->user->userMeta->profile_image ? asset('public/' . $reply->user->userMeta->profile_image) : null,
                            'created_at' => $reply->user->userMeta->created_at,
                        ],
                        'reactions' => $reply->reactions->map(function ($reactions) {
                            return [
                                'id' => $reactions->id,
                                'user_id' => $reactions->user_id,

                            ];
                        }),
                        'reactionCount' => $reply->reactions->count(),
                    ];
                }),
                'reactionCount' => $comment->reactions->count(),
                'reactions' => $comment->reactions->map(function ($reactions) {
                    return [
                        'id' => $reactions->id,
                        'user_id' => $reactions->user_id,

                    ];
                }),
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

    public function update(Request $request, $id)
    {
        $comment = Comment::find($id);
        if (!$comment) {
            return response()->json([
                "message" => "Comment not found",
            ]);
        }

        $validator = Validator::make($request->all(), [
            'comment' => 'required|string|max:1000',
        ])->stopOnFirstFailure();;

        if ($validator->fails()) {
            // Get the first error message
            $firstError = $validator->errors()->first();
            return response()->json(['error' => $firstError], 422);
        }

        // Update the comment
        $comment->comment = $request->input('comment');
        $comment->save();

        // Return the updated comment with a success message
        return response()->json([
            "message" => "Comment updated successfully",
            "comment" => $comment
        ], 200);
    }
}
