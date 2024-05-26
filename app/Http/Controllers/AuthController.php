<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\UserMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        # By default we are using here auth:api middleware
        $this->middleware('auth:api', ['except' => ['login']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {

        $credentials = request(["email", "password"]);

        if (!$token = auth()->attempt($credentials)) {
            return response()->json([
                "error" => "Unauthorized"
            ], 401);
        }
        // AuthController::getClientProfile();
        return $this->respondWithToken($token); # If all credentials are correct - we are going to generate a new access token and send it back on response
        # If all credentials are correct - we are going to generate a new access token and send it back on response
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getClientProfile()
    {
        # Here we just get information about current user
        // return response()->json(auth()->user());
        $user = auth()->user();
        $userMeta = $user->userMeta;
        return response()->json([
            "id" => auth()->user()->id,
            "email" => auth()->user()->email,
            "name" => $userMeta->name,
            "country_code" => $userMeta->country_code,
            "phone_number" => $userMeta->phone_number,
            "cover_image" => asset('/') . $userMeta->cover_image,
            "profile_image" => asset('/') . $userMeta->profile_image,
            "created_at" => $userMeta->created_at,
        ]);
    }

    //update profile information
    public function updateProfile(Request $request)
    {
        // Validate the request data
        $request->validate([
            'email' => 'sometimes|email|unique:users,email,' . auth()->user()->id,
            'name' => 'sometimes|string|max:255',
            'country_code' => 'sometimes|string|max:10',
            'phone_number' => 'sometimes|string|max:15|unique:user_metas,phone_number,' . auth()->user()->userMeta->id,
            'cover_image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'profile_image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Get the authenticated user
        $user = auth()->user();
        $userMeta = $user->userMeta;

        // Update user email if provided
        if ($request->has('email')) {
            $user->email = $request->email;
            $user->save();
        }

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            // Delete old cover image if it exists
            if ($userMeta->cover_image && File::exists(public_path($userMeta->cover_image))) {
                File::delete(public_path($userMeta->cover_image));
            }
            // Store new cover image
            $coverImage = $request->file('cover_image');
            $coverImageName = time() . '_' . $coverImage->getClientOriginalName();
            $coverImagePath = $coverImage->move(public_path('client/cover'), $coverImageName);
            $userMeta->cover_image = 'client/cover/' . $coverImageName;
        }

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            // Delete old profile image if it exists
            if ($userMeta->profile_image && File::exists(public_path($userMeta->profile_image))) {
                File::delete(public_path($userMeta->profile_image));
            }
            // Store new profile image
            $profileImage = $request->file('profile_image');
            $profileImageName = time() . '_' . $profileImage->getClientOriginalName();
            $profileImagePath = $profileImage->move(public_path('client/profile'), $profileImageName);
            $userMeta->profile_image = 'client/profile/' . $profileImageName;
        }

        // Update user meta information if provided
        $userMeta->update($request->only('name', 'country_code', 'phone_number'));

        // Return the updated user and user meta information
        return response()->json([
            "id" => $user->id,
            "email" => $user->email,
            "name" => $userMeta->name,
            "country_code" => $userMeta->country_code,
            "phone_number" => $userMeta->phone_number,
            "cover_image" => $userMeta->cover_image ? url($userMeta->cover_image) : null,
            "profile_image" => $userMeta->profile_image ? url($userMeta->profile_image) : null,
            "created_at" => $userMeta->created_at,
        ]);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout(); # This is just logout function that will destroy access token of current user

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        # When access token will be expired, we are going to generate a new one wit this function
        # and return it here in response
        return $this->respondWithToken(auth::refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        $expiration = Auth::factory()->getTTL() * 60;
        # This function is used to make JSON response with new
        # access token of current user
        $user = auth()->user();
        $userMeta = $user->userMeta;
        return response()->json([
            "access_token" => $token,
            "token_type" => "bearer",
            "user" => [
                "user_id" => $userMeta->user_id,
                "name" => $userMeta->name,
                "country_code" => $userMeta->country_code,
                "phone_number" => $userMeta->phone_number,
                "cover_image" => asset('/') . $userMeta->cover_image,
                "profile_image" => asset('/') . $userMeta->profile_image,
                "created_at" => $userMeta->created_at,
            ],
            // "expires_in" => $expiration // Uncomment if you have expiration info
        ]);
    }
    protected function get()
    {

        return response()->json([
            "message" => "success",
            "token_type" => "bearer",

        ]);
    }
}
