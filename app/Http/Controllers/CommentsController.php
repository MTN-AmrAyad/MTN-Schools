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

    // public function index($video_id)
    // {

    //     // $video = Video::with(['comments' => function ($query) {
    //     //     $query->whereNull('parent_id')
    //     //         ->orderBy('created_at', 'desc')  // Order comments by created_at in descending order
    //     //         ->with('replies.user.userMeta', 'reactions', 'user.userMeta');
    //     // }])->findOrFail($video_id);

    //     $offset = 0;  // You can set the desired offset here
    //     $limit = 5;   // You can set the desired limit here

    //     $video = Video::with(['comments' => function ($query) use ($offset, $limit) {
    //         $query->whereNull('parent_id')
    //             ->orderBy('created_at', 'desc')  // Order comments by created_at in descending order
    //             ->skip($offset)  // Skip the first $offset comments
    //             ->take($limit)  // Take the next $limit comments
    //             ->with('replies.user.userMeta', 'reactions', 'user.userMeta');
    //     }])->findOrFail($video_id);

    //     $comments = $video->comments->map(function ($comment) {
    //         return [
    //             'id' => $comment->id,
    //             'comment' => $comment->comment,
    //             'user_id' => $comment->user_id,
    //             'video_id' => $comment->video_id,
    //             'created_at' => $comment->created_at,
    //             'userMeta' => [
    //                 'name' => $comment->user->userMeta->name,
    //                 'country_code' => $comment->user->userMeta->country_code,
    //                 'phone_number' => $comment->user->userMeta->phone_number,
    //                 'cover_image' => $comment->user->userMeta->cover_image ? asset('public/' . $comment->user->userMeta->cover_image) : null,
    //                 'profile_image' => $comment->user->userMeta->profile_image ? asset('public/' . $comment->user->userMeta->profile_image) : null,
    //                 'created_at' => $comment->user->userMeta->created_at,
    //             ],
    //             'replies' => $comment->replies->map(function ($reply) {
    //                 return [
    //                     'id' => $reply->id,
    //                     'comment' => $reply->comment,
    //                     'user_id' => $reply->user_id,
    //                     'parent_id' => $reply->parent_id,
    //                     'created_at' => $reply->created_at,
    //                     'userMeta' => [
    //                         'name' => $reply->user->userMeta->name,
    //                         'country_code' => $reply->user->userMeta->country_code,
    //                         'phone_number' => $reply->user->userMeta->phone_number,
    //                         'cover_image' => $reply->user->userMeta->cover_image ? asset('public/' . $reply->user->userMeta->cover_image) : null,
    //                         'profile_image' => $reply->user->userMeta->profile_image ? asset('public/' . $reply->user->userMeta->profile_image) : null,
    //                         'created_at' => $reply->user->userMeta->created_at,
    //                     ],
    //                     'reactions' => $reply->reactions->map(function ($reactions) {
    //                         return [
    //                             'id' => $reactions->id,
    //                             'user_id' => $reactions->user_id,

    //                         ];
    //                     }),
    //                     'reactionCount' => $reply->reactions->count(),
    //                 ];
    //             }),
    //             'reactionCount' => $comment->reactions->count(),
    //             'reactions' => $comment->reactions->map(function ($reactions) {
    //                 return [
    //                     'id' => $reactions->id,
    //                     'user_id' => $reactions->user_id,

    //                 ];
    //             }),
    //         ];
    //     });

    //     return response()->json([
    //         "message" => "Comments retrieved successfully",
    //         "data" => $comments
    //     ]);
    // }
    public function index($video_id)
    {
        $video = Video::findOrFail($video_id);

        // Paginate the comments with eager loading for replies, reactions, and user metadata
        $comments = Comment::where('video_id', $video_id)
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc')
            ->with(['replies.user.userMeta', 'reactions', 'user.userMeta'])
            ->paginate(2); // Paginate with 5 comments per page

        // Transform the paginated comments
        $comments->getCollection()->transform(function ($comment) {
            return [
                'id' => $comment->id,
                'comment' => $comment->comment,
                'user_id' => $comment->user_id,
                'user_name' => $comment->user->userMeta->name,
                'profile_image' => $comment->user->userMeta->profile_image ? asset('profiles/' . $comment->user->userMeta->profile_image) : null,
                'created_at' => $comment->created_at,
                'reactionCount' => $comment->reactions->count(),
                'replies' => $comment->replies->map(function ($reply) {
                    return [
                        'id' => $reply->id,
                        'comment' => $reply->comment,
                        'user_id' => $reply->user_id,
                        'user_name' => $reply->user->userMeta->name,
                        'profile_image' => $reply->user->userMeta->profile_image ? asset('profiles/' . $reply->user->userMeta->profile_image) : null,
                        'created_at' => $reply->created_at,
                        'reactions' => $reply->reactions,
                        'parent_id' => $reply->parent_id,
                        'reactionCount' => $reply->reactions->count(),
                    ];
                }),
                'reactions' => $comment->reactions,

            ];
        });

        // Get pagination data
        $paginationData = $comments->toArray();
        unset($paginationData['data']); // Remove the actual data to return in a separate key

        return response()->json([
            "message" => "Comments retrieved successfully",
            "total_comments" => $comments->total(), // Total count of comments
            "data" => $comments->items(),
            "pagination" => [
                "total" => $paginationData['total'],
                "per_page" => $paginationData['per_page'],
                "current_page" => $paginationData['current_page'],
                "last_page" => $paginationData['last_page'],
                "from" => $paginationData['from'],
                "to" => $paginationData['to'],
                "path" => $paginationData['path'],
                "first_page_url" => $paginationData['first_page_url'],
                "last_page_url" => $paginationData['last_page_url'],
                "next_page_url" => $paginationData['next_page_url'],
                "prev_page_url" => $paginationData['prev_page_url'],
            ],
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
