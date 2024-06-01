<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function getUserGroups()
    {
        $user = Auth::user();
        $groups = $user->groups;

        return response()->json($groups);
    }
}
