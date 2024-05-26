<?php

namespace App\Http\Controllers;

use App\Models\Calendar;
use App\Models\Group;
use Illuminate\Http\Request;

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
            ];
        });

        return response()->json($calendars);
    }

    // Create a new calendar event
    public function store(Request $request, Group $group)
    {

        $request->validate([
            'title' => 'required|string|max:255',
            'start' => 'required|date',
            'end' => 'nullable|date',
            'allDay' => 'required|boolean',
        ]);

        $calendar = $group->calendars()->create([
            'title' => $request->title,
            'start' => $request->start,
            'end' => $request->end,
            'allDay' => $request->allDay,
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
        ]);

        $calendar->update([
            'title' => $request->title,
            'start' => $request->start,
            'end' => $request->end,
            'allDay' => $request->allDay,
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
        $calendar->delete();

        return response()->json(['message' => 'Event deleted successfully']);
    }
}
