<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostVideo;
use App\Models\PostImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File; // Add this line for file operations


class PostController extends Controller
{

    public function index()
    {
        $posts = Post::with(['user', 'videos', 'images', 'comments', 'likes'])->get();
        return response()->json($posts);
    }

    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'videos' => 'nullable|file|mimes:mp4,avi,mov,wmv|max:20480',
            'images.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:20480',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        // Create the post
        $post = Post::create([
            'user_id' => auth()->id(),
            'title' => $request->title,
            'content' => $request->content,
        ]);
        $postId = $post->id;

        // Upload videos if present
        if ($request->hasFile('videos')) {
            $video = $request->file('videos');
            $videoName = time() . '_' . $video->getClientOriginalName();
            $videoPath = $video->move(public_path('post/videos'), $videoName);

            if ($videoPath) {
                PostVideo::create(['post_id' => $postId, 'video_path' => 'post/videos/' . $videoName]);
            } else {
                return response()->json(['error' => 'Video upload failed'], 500);
            }
        }

        // Upload images if present
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                if ($image->isValid()) {
                    $imageName = time() . '_' . $image->getClientOriginalName();
                    $imagePath = $image->move(public_path('post/images'), $imageName);

                    if ($imagePath) {
                        PostImage::create(['post_id' => $postId, 'image_path' => 'post/images/' . $imageName]);
                    } else {
                        return response()->json(['error' => 'Image upload failed'], 500);
                    }
                } else {
                    return response()->json(['error' => 'Invalid file'], 400);
                }
            }
        }

        return response()->json($post->load(['videos', 'images']));
    }

    public function show($id)
    {
        $post = Post::with(['user', 'videos', 'images', 'comments', 'likes'])->find($id);

        if (!$post) {
            return response()->json([
                "message" => "The post with ID {$id} was not found",
            ], 404);
        }

        // Prepare the response data
        $responseData = [
            'id' => $post->id,
            'user_id' => $post->user_id,
            'title' => $post->title,
            'content' => $post->content,
            'created_at' => $post->created_at,
            'updated_at' => $post->updated_at,
            'user' => $post->user, // Assuming user relationship is correctly defined
            'videos' => $post->videos,
            'images' => $this->getImageUrls($post->images), // Get asset URLs for images
            'comments' => $post->comments,
            'likes' => $post->likes,
        ];

        return response()->json($responseData);
    }

    public function update(Request $request, $id)
    {
        $post = Post::findOrFail($id);

        // Validate the request
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'videos' => 'nullable|file|mimes:mp4,avi,mov,wmv|max:20480',
            'images.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:20480',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        // Update the post with title and content
        $post->update([
            'title' => $request->title,
            'content' => $request->content,
        ]);

        // Handle video upload if present (similar to previous implementation)
        if ($request->hasFile('videos')) {
            $this->uploadVideos($request->file('videos'), $post->id);
        }

        // Handle image upload and deletion if present
        if ($request->hasFile('images')) {
            $this->deleteOldImages($post); // Delete old images first
            $this->uploadImages($request->file('images'), $post->id);
        }

        // Reload the updated post with images
        $post->refresh();

        return response()->json($post->load(['videos', 'images']));
    }

    public function destroy($id)
    {
        $post = Post::findOrFail($id);

        // Delete associated images
        foreach ($post->images as $image) {
            $imagePath = public_path($image->image_path);

            if (File::exists($imagePath)) {
                File::delete($imagePath); // Delete the image file
            }

            $image->delete(); // Delete the image record from database
        }

        // Delete the post itself
        $post->delete();

        return response()->json(['message' => 'Post deleted successfully']);
    }

    // protected function uploadVideos($videos, $postId)
    // {
    //     foreach ($videos as $video) {
    //         $filename = time() . '_' . $video->getClientOriginalName();
    //         $path = $video->move(public_path('post/videos'), $filename);
    //         PostVideo::create(['post_id' => $postId, 'video_path' => 'post/videos/' . $filename]);
    //     }
    // }

    private function uploadImages($images, $postId)
    {
        foreach ($images as $image) {
            if ($image->isValid()) {
                $imageName = time() . '_' . $image->getClientOriginalName();
                $imagePath = $image->move(public_path('post/images'), $imageName);

                if ($imagePath) {
                    PostImage::create(['post_id' => $postId, 'image_path' => 'post/images/' . $imageName]);
                } else {
                    return response()->json(['error' => 'Image upload failed'], 500);
                }
            } else {
                return response()->json(['error' => 'Invalid file'], 400);
            }
        }
    }
    private function deleteOldImages($post)
    {
        foreach ($post->images as $image) {
            // Delete the image file from the storage directory
            $imagePath = public_path($image->image_path);

            if (File::exists($imagePath)) {
                File::delete($imagePath);
            }

            // Delete the image record from the database
            $image->delete();
        }
    }


    //this finction to read image in response
    private function getImageUrls($images)
    {
        $imageUrls = [];
        foreach ($images as $image) {
            $imageUrl = asset($image->image_path); // Construct full URL for each image
            $imageUrls[] = [
                'id' => $image->id,
                'post_id' => $image->post_id,
                'image_path' => $imageUrl,
                'created_at' => $image->created_at,
                'updated_at' => $image->updated_at,
            ];
        }
        return $imageUrls;
    }
}
