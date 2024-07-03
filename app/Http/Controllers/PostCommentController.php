<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use App\Models\PostComment;

class PostCommentController extends Controller
{
    public function store(Request $request, $postId)
    {
        $checkID = Post::where('id', $postId)->first();
        if (!$checkID) {
            return response()->json([
                "message" => "ID not found",
            ], 401);
        }
        $request->validate([
            'comment' => 'required|string',
        ]);

        $comment = PostComment::create([
            'user_id' => auth()->id(),
            'post_id' => $postId,
            'comment' => $request->comment,
        ]);

        return response()->json($comment);
    }

    public function update(Request $request, $id)
    {
        $comment = PostComment::findOrFail($id);
        $comment->update($request->only(['comment']));

        return response()->json($comment);
    }

    public function destroy($id)
    {
        $comment = PostComment::findOrFail($id);
        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully']);
    }
}
