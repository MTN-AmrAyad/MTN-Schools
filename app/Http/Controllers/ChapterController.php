<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
    public function getChaptersByRound($round_id)
    {
        $chapters = Chapter::where('round_id', $round_id)->with('round', 'videos')->get();

        if ($chapters->isEmpty()) {
            return response()->json(['message' => 'No chapters found for this round'], 404);
        }

        return response()->json($chapters);
    }
}
