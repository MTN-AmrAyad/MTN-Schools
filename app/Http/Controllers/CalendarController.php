<?php

namespace App\Http\Controllers;

use App\Models\Calendar;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CalendarController extends Controller
{
    // Get all calendar events for a group
    public function index(Group $group)
    {
        $calendars = $group->calendars->map(function ($calendar) {
            return [
                'id' => $calendar->id,
                'title' => $calendar->title,
                'start' => $calendar->start,
                'end' => $calendar->end,
                'allDay' => $calendar->allDay,
                'event_desc' => $calendar->event_desc,
                'event_img' => asset('events/' . $calendar->event_img),
            ];
        });

        return response()->json($calendars);
    }

    // Create a new calendar event
    public function store(Request $request, Group $group)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'start' => 'required|date',
            'end' => 'nullable|date',
            'allDay' => 'required|boolean',
            'event_desc' => 'required',
            'event_img' => 'image|mimes:jpeg,png,jpg,gif,svg|max:10248',
        ])->stopOnFirstFailure();;

        if ($validator->fails()) {
            // Get the first error message
            $firstError = $validator->errors()->first();
            return response()->json(['error' => $firstError], 422);
        }

        $imageName = null;
        if ($request->hasFile('event_img')) {
            $imageName = time() . '.' . $request->event_img->getClientOriginalExtension();
            $request->event_img->move(public_path('events'), $imageName);
        }

        $calendar = $group->calendars()->create([
            'title' => $request->title,
            'start' => $request->start,
            'end' => $request->end,
            'allDay' => $request->allDay,
            'event_desc' => $request->event_desc,
            'event_img' => $imageName,
        ]);

        return response()->json(['message' => 'Event created successfully', 'calendar' => $calendar], 201);
    }

    // Get a specific calendar event
    public function show($id)
    {
        $calendar = Calendar::find($id);
        if (!$calendar) {
            return response()->json([
                "message" => "ID not found",
            ], 422);
        }


        return response()->json([
            'id' => $calendar->id,
            'title' => $calendar->title,
            'start' => $calendar->start,
            'end' => $calendar->end,
            'allDay' => $calendar->allDay,
            'event_desc' => $calendar->event_desc,
            'group_id' => $calendar->group_id,
            'event_img' => asset('events/' . $calendar->event_img),
        ]);
    }

    // Update a calendar event
    public function update(Request $request, $id)
    {
        $calendar = Calendar::find($id);
        if (!$calendar) {
            return response()->json([
                "message" => "ID not found",
            ], 422);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'start' => 'required|date',
            'end' => 'nullable|date',
            'allDay' => 'required|boolean',
            'event_desc' => 'required',
            'event_img' => 'image|mimes:jpeg,png,jpg,gif,svg|max:10248',
        ]);

        $imageName = $calendar->event_img;
        if ($request->hasFile('event_img')) {
            // Delete the old image if it exists
            if ($imageName) {
                $oldImagePath = public_path('events/' . $imageName);
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            $imageName = time() . '.' . $request->event_img->getClientOriginalExtension();
            $request->event_img->move(public_path('events'), $imageName);
        }

        $calendar->update([
            'title' => $request->title,
            'start' => $request->start,
            'end' => $request->end,
            'allDay' => $request->allDay,
            'event_desc' => $request->event_desc,
            'event_img' => $imageName,
        ]);

        return response()->json(['message' => 'Event updated successfully', 'calendar' => $calendar]);
    }

    // Delete a calendar event
    public function destroy($id)
    {
        $calendar = Calendar::find($id);
        if (!$calendar) {
            return response()->json([
                "message" => "ID not found",
            ], 422);
        }

        // Delete the image if it exists
        if ($calendar->event_img) {
            $imagePath = public_path('events/' . $calendar->event_img);
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        $calendar->delete();

        return response()->json(['message' => 'Event deleted successfully']);
    }

    //get zoom meeting auth for each event in the each group
    public function getMeetingZoom($id)
    {
        $calendar = Calendar::find($id);
        if (!$calendar) {
            return response()->json([
                "message" => "ID not found",
            ], 422);
        }
        $zoomMeeting = Group::where('id', $calendar->group_id)->first();

        return response()->json([
            "meetingNumber" => $zoomMeeting->meetingNumber,
            "meetingPassword" => $zoomMeeting->meetingPassword,
        ]);
    }
}
