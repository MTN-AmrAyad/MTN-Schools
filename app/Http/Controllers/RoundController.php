<?php

namespace App\Http\Controllers;

use App\Models\Round;
use App\Models\UserProgress;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class RoundController extends Controller
{
    // Get all rounds
    public function index()
    {
        $rounds = Round::with('group', 'chapters')->get();
        if (!$rounds) {
            return response()->json([
                "message" => "data not found",
            ], 422);
        }
        $data = [];
        foreach ($rounds as $round) {
            $data[] = [
                "id" => $round->id,
                "round_name" => $round->round_name,
                "round_desc" => $round->round_desc,
                "round_cover" => asset('rounds/' . $round->round_cover),
                "group_id" => $round->group_id,
            ];
        }
        return response()->json([
            "message" => "data retrieved successfully",
            "data" => $data
        ]);
    }

    // Create a new round
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group_id' => 'required|exists:groups,id',
            'round_name' => 'required|string|max:255|unique:rounds,round_name',
            'round_desc' => 'required|string',
            'round_cover' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ])->stopOnFirstFailure();;

        if ($validator->fails()) {
            // Get the first error message
            $firstError = $validator->errors()->first();
            return response()->json(['error' => $firstError], 422);
        }

        $imageName = time() . '.' . $request->round_cover->getClientOriginalExtension();
        $request->round_cover->move(public_path('rounds'), $imageName);

        $round = Round::create([
            'group_id' => $request->group_id,
            'round_name' => $request->round_name,
            'round_desc' => $request->round_desc,
            'round_cover' => $imageName,
        ]);

        return response()->json(['message' => 'Round created successfully', 'round' => $round], 201);
    }

    // Get one round
    public function show($id)
    {
        $round = Round::with('group', 'chapters')->find($id);
        if (!$round) {
            return response()->json([
                "message" => "ID not found",
            ], 422);
        }
        $round->round_cover = asset('rounds/' . $round->round_cover);
        return response()->json($round);
    }

    // Update a round
    public function update(Request $request, $id)
    {
        $round = Round::find($id);
        if (!$round) {
            return response()->json([
                "message" => "ID not found",
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'group_id' => 'required|exists:groups,id',
            'round_name' => 'required|string|max:255|unique:rounds,round_name',
            'round_desc' => 'required|string',
            'round_cover' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ])->stopOnFirstFailure();;

        if ($validator->fails()) {
            // Get the first error message
            $firstError = $validator->errors()->first();
            return response()->json(['error' => $firstError], 422);
        }

        if ($request->hasFile('round_cover')) {
            $oldImagePath = public_path('rounds/' . $round->round_cover);
            if (File::exists($oldImagePath)) {
                File::delete($oldImagePath);
            }

            $imageName = time() . '.' . $request->round_cover->getClientOriginalExtension();
            $request->round_cover->move(public_path('rounds'), $imageName);
            $round->round_cover = $imageName;
        }

        $round->update([
            'group_id' => $request->group_id,
            'round_name' => $request->round_name,
            'round_desc' => $request->round_desc,
        ]);

        return response()->json(['message' => 'Round updated successfully', 'round' => $round]);
    }

    // Delete a round
    public function destroy($id)
    {
        $round = Round::find($id);
        if (!$round) {
            return response()->json([
                "message" => "ID not found",
            ], 422);
        }

        $imagePath = public_path('rounds/' . $round->round_cover);
        if (File::exists($imagePath)) {
            File::delete($imagePath);
        }
        $round->delete();
        return response()->json(['message' => 'Round deleted successfully']);
    }
    // Get Round by group_id
    public function getChaptersByRound($group_id)
    {
        $rounds = Round::where('group_id', $group_id)->with('chapters')->get();

        if ($rounds->isEmpty()) {
            return response()->json(['message' => 'No round found for this round'], 404);
        }
        // Transform the rounds collection to include the chapters count
        $rounds = $rounds->map(function ($round) {
            return [
                'group_id' => $round->id,
                'round_name' => $round->round_name,
                'round_desc' => $round->round_desc,
                'round_cover' => $round->round_cover,
                'chapters' => $round->chapters->count(),
                // 'chapters' => $round->chapters
            ];
        });

        return response()->json([
            'rounds' => $rounds,
        ]);
    }

    public function getChaptersByRoundtwo($group_id)
    {
        // Retrieve all rounds with their chapters
        $rounds = Round::where('group_id', $group_id)->with('chapters')->get();

        // Get authenticated user ID
        $user_id = auth()->id();

        // Retrieve user progress for the specified group
        $progress = UserProgress::where('user_id', $user_id)
            ->where('group_id', $group_id)
            ->get();

        // Count completed videos
        $completedVideos = $progress->where('is_completed', true)->count();

        // Count total videos in the group
        $totalVideos = Video::whereHas('chapter.round', function ($query) use ($group_id) {
            $query->where('group_id', $group_id);
        })->count();

        // Initialize an array to store the rounds with their completion percentages
        $formattedRounds = [];

        // Calculate completion percentage for each round
        foreach ($rounds as $round) {
            $check = UserProgress::where('round_id', $round->id)
                ->where('user_id', $user_id)
                ->first();
            $roundData = [
                'id' => $round->id,
                'group_id' => $round->group_id,
                'round_name' => $round->round_name,
                'round_desc' => $round->round_desc,
                'round_cover' => $round->round_cover,
                'chapters' => $round->chapters->count(),
                // 'isLocked' => $check, // Assuming default is locked unless calculated otherwise
                'completion_percentage' => 0,
            ];
            // return response()->json($roundData);
            // Calculate total videos in the current round
            $totalVideosInRound = $round->chapters->flatMap->videos->count();

            if ($totalVideosInRound > 0) {
                // Calculate completed videos in the current round
                $completedVideosInRound = $progress->whereIn('video_id', $round->chapters->flatMap->videos->pluck('id'))
                    ->where('is_completed', true)
                    ->count();

                // Calculate completion percentage for the round
                $roundData['completion_percentage'] = ($completedVideosInRound / $totalVideosInRound) * 100;

                // Determine if the round is locked based on completion percentage
                $roundData['is_locked'] = $check == null;
            }

            $formattedRounds[] = $roundData;
        }

        // Return the response as JSON
        return response()->json($formattedRounds);
    }
}
