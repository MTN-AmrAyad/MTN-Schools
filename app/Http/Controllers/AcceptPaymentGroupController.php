<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcceptPaymentGroup;
use App\Models\Group;
use App\Models\Round;
use App\Models\UserProgress;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AcceptPaymentGroupController extends Controller
{
    // public function store(Request $request)
    // {

    //     // Validate the request data
    //     $request->validate([
    //         'user_id' => 'required|exists:users,id',
    //         'group_id' => 'required|exists:groups,id',
    //     ]);

    //     $check = AcceptPaymentGroup::where('user_id', $request->user_id)
    //         ->where('group_id', $request->group_id)->first();
    //     if (!$check) {
    //         // Create a new accept_payment_group entry
    //         AcceptPaymentGroup::create($request->all());

    //         return response()->json([
    //             "message" => "success",
    //         ]);
    //     }
    //     if ($check->status === "needRenew") {
    //         return response()->json([
    //             "message" => "Client joiund the group but need to Renew"
    //         ]);
    //     } else if ($check->status === "paid") {
    //         return response()->json([
    //             "message" => "Client joiund the group has been paid"
    //         ]);
    //     }
    // }

    public function store(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'group_id' => 'required|exists:groups,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $userId = Auth::id(); // Get the authenticated user's ID
        $groupId = $request->input('group_id');

        $check = AcceptPaymentGroup::where('user_id', $userId)
            ->where('group_id', $groupId)
            ->first();

        if (!$check) {
            // Create a new accept_payment_group entry
            AcceptPaymentGroup::create([
                'user_id' => $userId,
                'group_id' => $groupId,
                'status' => 'paid', // Assuming default status is paid when created
            ]);
            

            $rounds = Round::where('group_id', $request->group_id)->with('chapters')->first();
            $round_id = $rounds->id;
            $chapterIds = $rounds->chapters->pluck('id')->first();
            $videos = Video::where('chapter_id', $chapterIds)->first();
            $video_id = $videos->id;
            UserProgress::create([
                'user_id' => $userId,
                'group_id' => $groupId,
                'round_id' => $round_id,
                'chapter_id' => $chapterIds,
                'video_id' => $video_id,
                'is_completed' => false,
            ]);

            return response()->json([
                "message" => "success",
            ]);
        }

        if ($check->status === "needRenew") {
            return response()->json([
                "message" => "Client joined the group but needs to renew",
            ]);
        } else if ($check->status === "paid") {
            return response()->json([
                "message" => "Client joined the group and has paid",
            ]);
        }
    }


    // public function checkSubscribton(Request $request)
    // {
    //     // Validate the request data
    //     $request->validate([
    //         'user_id' => 'required|exists:users,id',
    //         'group_id' => 'required|exists:groups,id',
    //     ]);

    //     $check = AcceptPaymentGroup::where('user_id', $request->user_id)
    //         ->where('group_id', $request->group_id)
    //         ->first();

    //     if (!$check) {
    //         return response()->json(['message' => 'Subscription not found'], 404);
    //     }

    //     // Calculate the time difference
    //     $createdAt = Carbon::parse($check->created_at);
    //     $currentTime = Carbon::now();
    //     // $timeDifferenceInMonths = $createdAt->diffInMonths($currentTime);
    //     $timeDifferenceInMinutes = $createdAt->diffInMinutes($currentTime);


    //     // Check if the time difference is greater than or equal to one month
    //     if ($timeDifferenceInMinutes >= 2) {
    //         $check->status = 'needRenew';
    //         $check->save();
    //     }

    //     return response()->json([
    //         'created_at' => $check->created_at,
    //         'current_time' => $currentTime,
    //         'time_difference_in_months' => $timeDifferenceInMinutes,
    //         'status' => $check->status,
    //     ]);
    // }

    public function checkSubscription(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'group_id' => 'required|exists:groups,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $userId = Auth::id(); // Get the authenticated user's ID
        $groupId = $request->input('group_id');

        $check = AcceptPaymentGroup::where('user_id', $userId)
            ->where('group_id', $groupId)
            ->first();

        if (!$check) {
            return response()->json([
                'status' => 'notSubscribed',
            ], 200);
        }

        // Calculate the time difference
        $createdAt = Carbon::parse($check->created_at);
        $currentTime = Carbon::now('Africa/Cairo');
        $timeDifferenceInMonths = $createdAt->diffInMonths($currentTime);
        // $timeDifferenceInMinutes = $createdAt->diffInMinutes($currentTime);

        // Check if the time difference is greater than or equal to 3 minutes
        if ($timeDifferenceInMonths >= 1) {
            $check->status = 'needRenew';
            $check->save();
        }

        return response()->json([
            'created_at' => $check->created_at,
            'current_time' => $currentTime,
            'time_difference_in_minutes' => $timeDifferenceInMonths,
            'status' => $check->status,
        ]);
    }

    public function renewSubscription(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'group_id' => 'required|exists:groups,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $userId = Auth::id(); // Get the authenticated user's ID
        $groupId = $request->input('group_id');

        $check = AcceptPaymentGroup::where('user_id', $userId)
            ->where('group_id', $groupId)
            ->first();

        if (!$check) {
            return response()->json([
                'status' => 'notSubscribed',
            ], 200);
        }
        $check->status = 'paid';
        $check->created_at = now('Africa/Cairo');
        $check->save();


        return response()->json([
            'message' => "renew payment success",
            'created_at' => $check->created_at,
            'status' => $check->status,
        ]);
    }

    public function delete(Request $request)
    {
        $user = Auth::user();
        $subscription = AcceptPaymentGroup::where('user_id', $user->id)
            ->where('group_id', $request->group_id)->first();
        $subscription->delete();
        return response()->json([
            "message" => "delete subscription ya mikol"
        ]);
    }
}
