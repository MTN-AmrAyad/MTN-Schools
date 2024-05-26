<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserMetaController extends Controller
{
    public function signup(Request $request)
    {
        // Validate request data
        $request->validate([
            'email' => 'required|unique:users,email',
            'password' => 'required',
            'name' => 'required',
            'country_code' => 'required',
            'phone_number' => 'required|unique:user_metas,phone_number',
        ]);

        // Use a transaction to ensure atomicity
        DB::beginTransaction();

        try {
            // Create the user
            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Create user meta data
            $user->userMeta()->create([
                'name' => $request->name,
                'country_code' => $request->country_code,
                'phone_number' => $request->phone_number,
            ]);

            // Commit the transaction
            DB::commit();

            return response()->json(['message' => 'User created successfully'], 201);
        } catch (\Exception $e) {
            // Rollback the transaction in case of any error
            DB::rollBack();

            return response()->json(['error' => 'User creation failed', 'message' => $e->getMessage()], 500);
        }
    }
}
