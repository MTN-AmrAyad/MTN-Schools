<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;


class GroupController extends Controller
{

    //Function to get all Groups
    public function index()
    {
        $groups =  Group::all();
        if (!$groups) {
            return response()->json([
                "message" => "data not found",
            ], 422);
        }
        $data = [];
        foreach ($groups as $group) {
            $data[] = [
                "id" => $group->id,
                "group_name" => $group->group_name,
                "group_desc" => $group->group_desc,
                "group_cover" => asset('group/' . $group->group_cover),
                "group_role" => $group->group_role,
                "price" => $group->price,
            ];
        }
        return response()->json([
            "message" => "data retrieved successfully",
            "data" => $data
        ]);
    }
    //Function to create a new Group and store it in the database
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "group_name" => "required|string|unique:groups,group_name",
            "group_desc" => "required|string",
            'group_cover' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'group_role' => 'required',
            'price' => 'required'
        ])->stopOnFirstFailure();;

        if ($validator->fails()) {
            // Get the first error message
            $firstError = $validator->errors()->first();
            return response()->json(['error' => $firstError], 422);
        }

        $imageName = time() . '.' . $request->group_cover->getClientOriginalExtension();
        $request->group_cover->move(public_path('group'), $imageName);

        $newGroup =  Group::create([
            'group_name' => $request->group_name,
            'group_desc' => $request->group_desc,
            'group_cover' => $imageName,
            'group_role' => $request->group_role,
            'meetingNumber' => $request->meetingNumber,
            'meetingPassword' => $request->meetingPassword,
            'price' => $request->price,
        ]);
        // Handle file upload
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                // Generate a unique name for each image
                $imageName = time() . '_' . $image->getClientOriginalName();
                // Move the image to the public/groupsImage directory
                $image->move(public_path('groupsImage'), $imageName);
                // Store the image path in the database
                GroupImage::create([
                    'group_id' => $newGroup->id,
                    'image_path' => 'groupsImage/' . $imageName,
                ]);
            }
        }

        if (!$newGroup) {
            return response()->json([
                "message" => "Faild to create group"
            ], 422);
        }
        return response()->json([
            "message" => "group created successfully"
        ], 201);
    }
    //function to get One Group from a Group table
    public function show($id)
    {
        $group =  Group::with('images', 'users.userMeta')->find($id);
        if (!$group) {
            return response()->json([
                "message" => "This id not found"
            ], 403);
        }
        return response()->json([
            "message" => "data retrieved successfully",
            "group" => [
                "id" => $group->id,
                "group_name" => $group->group_name,
                "group_desc" => $group->group_desc,
                "group_cover" =>  asset('group/' . $group->group_cover),
                "group_role" => $group->group_role,
                "price" => $group->price,
                "images" => $group->images->map(function ($image) {
                    return asset($image->image_path);
                }),
                "group_members" => $group->users->map(function ($user) {
                    return [
                        "id" => $user->id,
                        "email" => $user->email,
                        "name" => $user->userMeta->name,
                        "image" => asset('public/' . $user->userMeta->profile_image),

                    ];
                }),

            ]

        ], 201);
    }
    //function to update each group
    public function update(Request $request, $id)
    {
        $group = Group::find($id);
        if (!$group) {
            return response()->json([
                "message" => "This id not found"
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            "group_name" => "required|string|unique:groups,group_name",
            "group_desc" => "required|string",
            'group_cover' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'group_role' => 'required'
        ])->stopOnFirstFailure();;

        if ($validator->fails()) {
            // Get the first error message
            $firstError = $validator->errors()->first();
            return response()->json(['error' => $firstError], 422);
        }

        if ($request->hasFile('group_cover')) {
            $oldImagePath = public_path('group/' . $group->group_cover);
            if (File::exists($oldImagePath)) {
                File::delete($oldImagePath);
            }

            $imageName = time() . '.' . $request->group_cover->getClientOriginalExtension();
            $request->group_cover->move(public_path('group'), $imageName);
        } else {
            $imageName = $group->group_cover;
        }

        $group->update([
            'group_name' => $request->group_name,
            'group_desc' => $request->group_desc,
            'group_cover' => $imageName,
            'group_role' => $request->group_role,
            'price' => $request->price,
        ]);

        return response()->json([
            "message" => "Successfully updated"
        ]);
    }
    //function to delete a group
    public function destroy($id)
    {
        $group = Group::find($id);
        if (!$group) {
            return response()->json([
                "message" => "This id not found"
            ], 403);
        }
        $imagePath = public_path('group/' . $group->group_cover);
        if (File::exists($imagePath)) {
            File::delete($imagePath);
        }
        $group->delete();
        return response()->json(['message' => 'Group deleted successfully']);
    }
    //public function to join a group
    public function joinGroup($groupId)
    {
        $user = Auth::user();
        $group = Group::find($groupId);

        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        // Check if the user is already in the group
        if ($user->groups->contains($groupId)) {
            return response()->json(['message' => 'User already a member of this group'], 400);
        }

        // Attach the user to the group
        $user->groups()->attach($group);

        return response()->json(['message' => 'Joined group successfully']);
    }

    //public function to Leave a group
    public function leaveGroup($groupId)
    {
        $user = Auth::user();
        $group = Group::find($groupId);

        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        $user->groups()->detach($group);

        return response()->json(['message' => 'Left group successfully']);
    }
    //function to get count of members of a group and the already members
    public function getGroupMembersWithMeta($groupId)
    {
        $group = Group::find($groupId);

        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        $members = $group->users()->with('userMeta')->get();

        $membersData = $members->map(function ($user) {

            return [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->userMeta->name ?? null,
                'country_code' => $user->userMeta->country_code ?? null,
                'phone_number' => $user->userMeta->phone_number ?? null,
                'cover_image' => $user->userMeta->cover_image ? asset('' . $user->userMeta->cover_image) : null,
                'profile_image' => $user->userMeta->profile_image ? asset('' . $user->userMeta->profile_image) : null,
                'created_at' => $user->created_at,

            ];
        });

        $count = $members->count();

        return response()->json([
            'count' => $count,
            'members' => $membersData
        ]);
    }
}
