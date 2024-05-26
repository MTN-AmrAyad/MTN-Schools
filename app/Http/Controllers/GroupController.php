<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

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
            'group_role' => 'required'
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
        ]);
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
        $group =  Group::find($id);
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
}
