<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\UserProgress;
use App\Models\Video;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChapterController extends Controller
{
    // Get all chapters
    public function index()
    {
        $chapters = Chapter::with('round', 'videos')->get();
        if (!$chapters) {
            return response()->json([
                "message" => "data not found",
            ], 422);
        }
        $data = [];
        foreach ($chapters as $chapter) {
            $data[] = [
                "id" => $chapter->id,
                "chapter_name" => $chapter->chapter_name,
                "round_id" => $chapter->round_id,
            ];
        }
        return response()->json([
            "message" => "data retrieved successfully",
            "data" => $data
        ]);
    }

    // Create a new chapter
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'round_id' => 'required|exists:rounds,id',
            'chapter_name' => 'required|string|max:255|unique:chapters,chapter_name',
        ])->stopOnFirstFailure();;

        if ($validator->fails()) {
            // Get the first error message
            $firstError = $validator->errors()->first();
            return response()->json(['error' => $firstError], 422);
        }

        $chapter = Chapter::create([
            'round_id' => $request->round_id,
            'chapter_name' => $request->chapter_name,
        ]);

        return response()->json(['message' => 'Chapter created successfully', 'chapter' => $chapter], 201);
    }

    // Get one chapter
    public function show($id)
    {
        $chapter = Chapter::with('round', 'videos')->findOrFail($id);
        return response()->json($chapter);
    }

    // Update a chapter
    public function update(Request $request, $id)
    {
        $chapter = Chapter::find($id);
        if (!$chapter) {
            return response()->json([
                "message" => "ID is not found"
            ]);
        }

        $validator = Validator::make($request->all(), [
            'round_id' => 'required|exists:rounds,id',
            'chapter_name' => 'required|string|max:255|unique:chapters,chapter_name',
        ])->stopOnFirstFailure();;

        if ($validator->fails()) {
            // Get the first error message
            $firstError = $validator->errors()->first();
            return response()->json(['error' => $firstError], 422);
        }

        $chapter->update([
            'round_id' => $request->round_id,
            'chapter_name' => $request->chapter_name,
        ]);

        return response()->json(['message' => 'Chapter updated successfully', 'chapter' => $chapter]);
    }

    // Delete a chapter
    public function destroy($id)
    {
        $chapter = Chapter::findOrFail($id);
        $chapter->delete();
        return response()->json(['message' => 'Chapter deleted successfully']);
    }

    // Get chapters by round_id
    // public function getChaptersByRound($round_id)
    // {
    //     $chapters = Chapter::where('round_id', $round_id)->with('round', 'videos')->get();

    //     if ($chapters->isEmpty()) {
    //         return response()->json(['message' => 'No chapters found for this round'], 404);
    //     }

    //     // Add the path to video_photo
    //     $chapters = $chapters->map(function ($chapter) {
    //         $chapter->videos = $chapter->videos->map(function ($video) {
    //             $video->video_photo = asset('videos/' . $video->video_photo);
    //             return $video;
    //         });
    //         return $chapter;
    //     });

    //     return response()->json($chapters);
    // }

    // public function getChaptersByRound(Request $request, $round_id)
    // {
    //     // Retrieve authenticated user
    //     $user = auth()->user();

    //     // Retrieve chapters with videos and round information
    //     $chapters = Chapter::where('round_id', $round_id)
    //         ->with(['videos' => function ($query) {
    //             $query->select('id', 'chapter_id', 'video_name', 'video_photo', 'video_link', 'created_at', 'updated_at', 'video_desc', 'author_name');
    //         }])
    //         ->with('round')
    //         ->get(['id', 'round_id', 'chapter_name', 'created_at', 'updated_at']);

    //     if ($chapters->isEmpty()) {
    //         return response()->json(['message' => 'No chapters found for this round'], 404);
    //     }

    //     // Get user progress
    //     $userProgress = UserProgress::where('user_id', $user->id)->pluck('video_id')->toArray();

    //     // Format response including videos and chapter details
    //     $formattedChapters = $chapters->map(function ($chapter) use ($userProgress) {
    //         $chapter->videos->each(function ($video) use ($userProgress) {
    //             $video->is_locked = !in_array($video->id, $userProgress);
    //             $video->video_photo = $video->video_photo ? asset('videos/' . $video->video_photo) : null;
    //             unset($video->chapter_id);
    //         });
    //         return $chapter;
    //     });

    //     return response()->json($formattedChapters);
    // }

    public function getChaptersByRound(Request $request, $round_id)
    {
        // Retrieve authenticated user
        $user = auth()->user();

        // Retrieve chapters with videos and round information
        $chapters = Chapter::where('round_id', $round_id)
            ->with(['videos' => function ($query) {
                $query->select('id', 'chapter_id', 'video_name', 'video_photo', 'video_link', 'created_at', 'updated_at', 'video_desc', 'author_name');
            }])
            ->with('round')
            ->get(['id', 'round_id', 'chapter_name', 'created_at', 'updated_at']);

        if ($chapters->isEmpty()) {
            return response()->json(['message' => 'No chapters found for this round'], 404);
        }

        // Get user progress
        $userProgress = UserProgress::where('user_id', $user->id)->pluck('video_id')->toArray();

        // Retrieve all videos in the round
        $allVideosInRound = Video::whereHas('chapter', function ($query) use ($round_id) {
            $query->where('round_id', $round_id);
        })->get(['id', 'chapter_id']);

        // Get the ID of the last video in the round
        $lastVideoInRoundId = $allVideosInRound->last()->id;

        // Format response including videos and chapter details
        $formattedChapters = $chapters->map(function ($chapter) use ($userProgress, $lastVideoInRoundId) {
            $chapter->videos->each(function ($video) use ($userProgress, $lastVideoInRoundId) {
                $video->is_locked = !in_array($video->id, $userProgress);
                $video->is_last_video = $video->id == $lastVideoInRoundId;
                $video->video_photo = $video->video_photo ? asset('videos/' . $video->video_photo) : null;
                unset($video->chapter_id);
            });
            return $chapter;
        });

        return response()->json($formattedChapters);
    }
}
