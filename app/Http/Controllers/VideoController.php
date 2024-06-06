<?php

namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class VideoController extends Controller
{
    // Get all videos
    public function index()
    {
        $videos = Video::with('chapter')->get();
        if (!$videos) {
            return response()->json([
                "message" => "data not found",
            ], 422);
        }
        $data = [];
        foreach ($videos as $video) {
            $data[] = [
                "id" => $video->id,
                "video_name" => $video->video_name,
                "video_photo" => $video->video_photo ? asset('videos/' . $video->video_photo) : null,
                "video_link" => $video->video_link,
                "chapter_id" => $video->chapter_id,
                "video_desc" => $video->video_desc,
                "author_name" => $video->author_name,
            ];
        }
        return response()->json([
            "message" => "data retrieved successfully",
            "data" => $data
        ]);
    }

    // Create a new video
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'chapter_id' => 'required|exists:chapters,id',
            'video_name' => 'required|string|max:255|unique:videos,video_name,',
            'video_photo' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'video_link' => 'required|string|max:255',
        ])->stopOnFirstFailure();;

        if ($validator->fails()) {
            // Get the first error message
            $firstError = $validator->errors()->first();
            return response()->json(['error' => $firstError], 422);
        }
        $photoName = null;
        if ($request->video_photo) {
            $photoName = time() . '.' . $request->video_photo->getClientOriginalExtension();
            $request->video_photo->move(public_path('videos'), $photoName);
        }

        $video = Video::create([
            'chapter_id' => $request->chapter_id,
            'video_name' => $request->video_name,
            'video_photo' => $photoName,
            'video_link' => $request->video_link,
            'video_desc' => $request->video_desc,
            'author_name' => $request->author_name,
        ]);

        return response()->json(['message' => 'Video created successfully', 'video' => $video], 201);
    }

    // Get one video
    public function show($id)
    {
        $video = Video::with('chapter')->find($id);
        if (!$video) {
            return response()->json([
                "message" => "ID not found",
            ]);
        }
        $video->video_photo = $video->video_photo ? asset('videos/' . $video->video_photo) : null;

        return response()->json($video);
    }

    // Update a video
    public function update(Request $request, $id)
    {
        $video = Video::find($id);
        if (!$video) {
            return response()->json([
                "message" => "ID not found"
            ]);
        }


        $validator = Validator::make($request->all(), [
            'chapter_id' => 'required|exists:chapters,id',
            'video_name' => 'required|string|max:255|unique:videos,video_name,',
            'video_photo' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'video_link' => 'required|string|max:255',
        ])->stopOnFirstFailure();;

        if ($validator->fails()) {
            // Get the first error message
            $firstError = $validator->errors()->first();
            return response()->json(['error' => $firstError], 422);
        }

        if ($request->hasFile('video_photo')) {
            // Delete the old photo if it exists
            if ($video->video_photo) {
                $oldPhotoPath = public_path('videos/' . $video->video_photo);
                if (File::exists($oldPhotoPath)) {
                    File::delete($oldPhotoPath);
                }
            }


            // Store the new photo
            $photoName = time() . '.' . $request->video_photo->getClientOriginalExtension();
            $request->video_photo->move(public_path('videos'), $photoName);
        } else {
            $photoName = null; // Set the photoName to null if no new photo is provided
        }

        $video->update([
            'chapter_id' => $request->chapter_id,
            'video_name' => $request->video_name,
            'video_photo' => $photoName,
            'video_link' => $request->video_link,
            'video_desc' => $request->video_desc,
            'author_name' => $request->author_name,
        ]);

        return response()->json(['message' => 'Video updated successfully', 'video' => $video]);
    }

    // Delete a video
    public function destroy($id)
    {
        $video = Video::findOrFail($id);
        $photoPath = public_path('videos/' . $video->video_photo);
        if (File::exists($photoPath)) {
            File::delete($photoPath);
        }
        $video->delete();
        return response()->json(['message' => 'Video deleted successfully']);
    }

    // Get Round by group_id
    public function getVideosByChapterId($chapter_id)
    {
        $chapters = Video::where('chapter_id', $chapter_id)->with('chapter')->get();

        if ($chapters->isEmpty()) {
            return response()->json(['message' => 'No video found for this chapter'], 404);
        }

        return response()->json($chapters);
    }

    public function saveVideo($videoId)
    {
        $user = Auth::user();
        $video = Video::find($videoId);

        if (!$video) {
            return response()->json(['message' => 'Video not found'], 404);
        }

        // Check if the video is already saved by the user
        if ($user->savedVideos()->where('video_id', $videoId)->exists()) {
            return response()->json(['message' => 'Video already saved'], 409);
        }

        // Attach the video to the user's saved videos
        $user->savedVideos()->attach($videoId);

        return response()->json(['message' => 'Video saved successfully'], 201);
    }

    // Get saved videos for the authenticated user
    public function getSavedVideos()
    {
        $user = Auth::user();
        $savedVideos = $user->savedVideos()->get();

        return response()->json(['saved_videos' => $savedVideos], 200);
    }
    public function unsaveVideo($videoId)
    {
        $user = Auth::user();
        $video = Video::find($videoId);

        if (!$video) {
            return response()->json(['message' => 'Video not found'], 404);
        }

        // Detach the video from the user's saved videos
        $user->savedVideos()->detach($videoId);

        return response()->json(['message' => 'Video unsaved successfully']);
    }

    // Like a video
    public function likeVideo($videoId)
    {
        $user = Auth::user();
        $video = Video::find($videoId);

        if (!$video) {
            return response()->json(['message' => 'Video not found'], 404);
        }

        // Check if the video is already liked by the user
        if ($user->likedVideos()->where('video_id', $videoId)->exists()) {
            return response()->json(['message' => 'Video already liked'], 409);
        }

        // Attach the video to the user's liked videos
        $user->likedVideos()->attach($videoId);

        return response()->json(['message' => 'Video liked successfully'], 201);
    }

    // Unlike a video
    public function unlikeVideo($videoId)
    {
        $user = Auth::user();
        $video = Video::find($videoId);

        if (!$video) {
            return response()->json(['message' => 'Video not found'], 404);
        }

        // Detach the video from the user's liked videos
        $user->likedVideos()->detach($videoId);

        return response()->json(['message' => 'Video unliked successfully'], 200);
    }

    // Get likes for a specific video
    public function getVideoLikes($videoId)
    {
        $video = Video::with('likes')->find($videoId);

        if (!$video) {
            return response()->json(['message' => 'Video not found'], 404);
        }

        return response()->json([
            'likes' => $video->likes,
            'likeCount' => $video->likes->count(),
        ], 200);
    }
}
