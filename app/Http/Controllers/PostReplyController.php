<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PostReply;


class PostReplyController extends Controller
{
    public function store(Request $request, $commentId)
    {
        $request->validate([
            'reply' => 'required|string',
        ]);

        $reply = PostReply::create([
            'user_id' => auth()->id(),
            'comment_id' => $commentId,
            'reply' => $request->reply,
        ]);

        return response()->json($reply);
    }

    public function update(Request $request, $id)
    {
        $reply = PostReply::find($id);
        if (!$reply) {
            return response()->json([
                "message" => "ID not found for update",
            ], 401);
        }
        $reply->update($request->only(['reply']));

        return response()->json($reply);
    }

    public function destroy($id)
    {
        $reply = PostReply::find($id);
        if (!$reply) {
            return response()->json([
                "message" => "ID not found for destroy",
            ]);
        }
        $reply->delete();

        return response()->json(['message' => 'Reply deleted successfully']);
    }
}
