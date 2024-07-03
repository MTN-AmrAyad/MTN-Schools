<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PostLike;


class PostLikeController extends Controller
{
    public function store($postId)
    {
        $like = PostLike::firstOrCreate([
            'user_id' => auth()->id(),
            'post_id' => $postId,
        ]);

        return response()->json($like);
    }

    public function destroy($postId)
    {
        $like = PostLike::where('user_id', auth()->id())->where('post_id', $postId)->firstOrFail();
        $like->delete();

        return response()->json(['message' => 'Like removed successfully']);
    }
}
