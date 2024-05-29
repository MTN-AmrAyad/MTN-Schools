<?php

namespace App\Http\Controllers;

use App\Models\Reaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReactionController extends Controller
{
    public function store(Request $request, $comment_id)
    {

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:like,wow,care,love,sad',
        ])->stopOnFirstFailure();;

        if ($validator->fails()) {
            // Get the first error message
            $firstError = $validator->errors()->first();
            return response()->json(['error' => $firstError], 422);
        }

        $reaction = Reaction::updateOrCreate(
            ['comment_id' => $comment_id, 'user_id' => auth()->id()],
            ['type' => $request->type]
        );

        return response()->json(['message' => 'Reaction added successfully', 'reaction' => $reaction], 201);
    }

    public function destroy($comment_id)
    {
        $reaction = Reaction::where('comment_id', $comment_id)->where('user_id', auth()->id())->first();

        if ($reaction) {
            $reaction->delete();
            return response()->json(['message' => 'Reaction removed successfully'], 200);
        }

        return response()->json(['message' => 'Reaction not found'], 404);
    }
}
