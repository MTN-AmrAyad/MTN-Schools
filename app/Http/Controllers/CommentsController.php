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
            $query->whereNull('parent_id')->with('replies', 'reactions');
        }])->findOrFail($video_id);

        return response()->json($video->comments);
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
