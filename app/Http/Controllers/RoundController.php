<?php

namespace App\Http\Controllers;

use App\Models\Round;
use Illuminate\Http\Request;
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
                "group_name" => $round->round_name,
                "group_desc" => $round->round_desc,
                "group_cover" => asset('rounds/' . $round->round_cover),
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
        $chapters = Round::where('group_id', $group_id)->with('group', 'chapters')->get();

        if ($chapters->isEmpty()) {
            return response()->json(['message' => 'No round found for this round'], 404);
        }

        return response()->json($chapters);
    }
}
