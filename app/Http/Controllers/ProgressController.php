<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Round;
use Illuminate\Http\Request;
use App\Models\UserProgress;
use App\Models\Video;
use Carbon\Carbon;

class ProgressController extends Controller
{

    public function availableVideo(Request $request)
    {
        $user = auth()->user();
        $group_id = $request->group_id;

        // Get the user's progress in the group
        $userProgress = UserProgress::where('user_id', $user->id)
            ->where('group_id', $group_id)
            ->get();

        // Get the first round in the group
        $firstRound = Round::where('group_id', $group_id)
            ->with(['chapters.videos'])
            ->orderBy('id', 'asc')
            ->first();

        // If the user has no progress, initialize with the first video of the first round
        if ($userProgress->isEmpty() && $firstRound) {
            $firstChapter = $firstRound->chapters->first();
            $firstVideo = $firstChapter ? $firstChapter->videos->first() : null;

            if ($firstVideo) {
                UserProgress::firstOrCreate([
                    'user_id' => $user->id,
                    'group_id' => $group_id,
                    'round_id' => $firstRound->id,
                    'chapter_id' => $firstChapter->id,
                    'video_id' => $firstVideo->id,
                ], ['is_completed' => false]);
            }

            // Refresh the user progress after initialization
            $userProgress = UserProgress::where('user_id', $user->id)
                ->where('group_id', $group_id)
                ->get();
        }

        // Format the response
        $response = [];

        foreach ($userProgress as $progress) {
            $video = Video::find($progress->video_id);
            $chapter = Chapter::find($progress->chapter_id);
            $round = Round::find($progress->round_id);

            $response[] = [
                'video_id' => $video->id,
                'video_title' => $video->title,
                'is_completed' => $progress->is_completed,
                'chapter_id' => $chapter->id,
                'chapter_title' => $chapter->title,
                'round_id' => $round->id,
                'round_title' => $round->title,
                // 'is_locked' => $progress->is_completed ? false : true,
            ];
        }

        return response()->json($response);
    }




    public function completeVideo(Request $request, $video_id)
    {
        $user = auth()->user();
        $video = Video::findOrFail($video_id);

        $progress = UserProgress::where('user_id', $user->id)
            ->where('video_id', $video->id)
            ->first();

        if (!$progress) {
            return response()->json(['message' => 'Video not found in progress'], 404);
        }

        $progress->is_completed = true;
        $progress->save();

        // Unlock the next video and get its ID
        $nextVideoId = $this->unlockNextVideo($user, $video);
        $videoInfo = Video::where('id', $nextVideoId)->first();

        // Check if the entire round is completed
        $currentRound = $video->chapter->round;
        $roundLocked = true;

        if ($this->isRoundCompleted($user, $currentRound)) {
            $this->unlockNextRound($user, $currentRound);
            $roundLocked = false; // Next round is now unlocked
        }

        if (!$videoInfo) {
            return response()->json([
                // 'roundLocked' => $roundLocked,
                'isLastVideo' => true
            ]);
        }

        return response()->json([
            'roundLocked' => $roundLocked,
            "id" => $videoInfo->id,
            "chapter_id" => $videoInfo->chapter_id,
            "video_name" => $videoInfo->video_name,
            "video_photo" => $videoInfo->video_photo ? asset('videos/' . $videoInfo->video_photo) : null,
            "video_link" => $videoInfo->video_link,
            "video_desc" => $videoInfo->video_desc,
            "created_at" => $videoInfo->created_at,
            "author_name" => $videoInfo->author_name,
        ]);
    }

    protected function unlockNextVideo($user, $currentVideo)
    {
        $chapter = $currentVideo->chapter;
        $nextVideo = $chapter->videos()
            ->where('id', '>', $currentVideo->id)
            ->orderBy('id', 'asc')
            ->first();

        if ($nextVideo) {
            UserProgress::firstOrCreate([
                'user_id' => $user->id,
                'group_id' => $currentVideo->chapter->round->group->id,
                'round_id' => $currentVideo->chapter->round->id,
                'chapter_id' => $chapter->id,
                'video_id' => $nextVideo->id,
            ], ['is_completed' => false]);

            return $nextVideo->id;
        }

        // If no next video in current chapter, check the next chapter
        $nextChapter = $chapter->round->chapters()
            ->where('id', '>', $chapter->id)
            ->orderBy('id', 'asc')
            ->first();

        if ($nextChapter) {
            $nextVideoInNextChapter = $nextChapter->videos()->orderBy('id', 'asc')->first();
            if ($nextVideoInNextChapter) {
                UserProgress::firstOrCreate([
                    'user_id' => $user->id,
                    'group_id' => $currentVideo->chapter->round->group->id,
                    'round_id' => $currentVideo->chapter->round->id,
                    'chapter_id' => $nextChapter->id,
                    'video_id' => $nextVideoInNextChapter->id,
                ], ['is_completed' => false]);

                return $nextVideoInNextChapter->id;
            }
        }

        // If no next video or chapter, return null
        return null;
    }

    private function unlockNextRound($user, $currentRound)
    {
        $nextRound = Round::where('group_id', $currentRound->group_id)
            ->where('id', '>', $currentRound->id)
            ->orderBy('id', 'asc')
            ->first();

        if ($nextRound) {
            $firstChapter = $nextRound->chapters->first();
            $firstVideo = $firstChapter->videos->first();

            UserProgress::firstOrCreate([
                'user_id' => $user->id,
                'group_id' => $nextRound->group_id,
                'round_id' => $nextRound->id,
                'chapter_id' => $firstChapter->id,
                'video_id' => $firstVideo->id,
            ], ['is_completed' => false]);
        }
    }

    protected function isRoundCompleted($user, $currentRound)
    {
        foreach ($currentRound->chapters as $chapter) {
            foreach ($chapter->videos as $video) {
                $progress = UserProgress::where('user_id', $user->id)
                    ->where('video_id', $video->id)
                    ->first();
                if (!$progress || !$progress->is_completed) {
                    return false;
                }
            }
        }
        return true;
    }
}
