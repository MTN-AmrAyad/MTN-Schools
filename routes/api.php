<?php

use App\Http\Controllers\AcceptPaymentGroupController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ChapterController;
use App\Http\Controllers\CommentsController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\ReactionController;
use App\Http\Controllers\RoundController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserMetaController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PostCommentController;
use App\Http\Controllers\PostReplyController;
use App\Http\Controllers\PostLikeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware("auth:sanctum")->get("/user", function (Request $request) {
//     return $request->user();
// });

// Route::fallback(function () {
//     return response()->json(['message' => 'Endpoint not found.'], 404);
// });
Route::group([
    "middleware" => "api",
    "prefix" => "auth"
], function ($router) {
    Route::post("signup", [UserMetaController::class, "signup"]);
    Route::post("login", [AuthController::class, "login"]);
    Route::post("logout", [AuthController::class, "logout"]);
    Route::post("client-profile", [AuthController::class, "getClientProfile"]);
    Route::post('update-profile', [AuthController::class, 'updateProfile']);
    // Route::post("refresh", [AuthController::class, "refresh"]);
});

// GROUP ROUTE
Route::get('/groups', [GroupController::class, 'index']); //read all groups
Route::post('/groups', [GroupController::class, 'store']); //create and store new group
Route::get('/groups/{id}', [GroupController::class, 'show']); //get One Group
Route::post('/groups/{id}', [GroupController::class, 'update']); //update each group
Route::delete('/groups/{id}', [GroupController::class, 'destroy']); //delete each group

/*/////////////////////////////// END OF ROUTE GROUPS //////////////////////////////////////*/

// ROUNDS ROUTE
Route::get('/rounds', [RoundController::class, 'index']); // Get all rounds
Route::post('/rounds', [RoundController::class, 'store']); // Create a new round
Route::get('/rounds/{id}', [RoundController::class, 'show']); // Get one round
Route::post('/rounds/{id}', [RoundController::class, 'update']); // Update a round
Route::delete('/rounds/{id}', [RoundController::class, 'destroy']); // Delete a round
// Route::get('/rounds/{group_id}/groups', [RoundController::class, 'getChaptersByRound']);
Route::get('/rounds/{group_id}/groups', [RoundController::class, 'getChaptersByRoundtwo'])->middleware('auth:api');

/*/////////////////////////////// END OF ROUTE ROUNDS //////////////////////////////////////*/


// CHAPTERS GROUPS
Route::get('/chapters', [ChapterController::class, 'index']); // Get all chapters
Route::post('/chapters', [ChapterController::class, 'store']); // Create a new chapter
Route::get('/chapters/{id}', [ChapterController::class, 'show']); // Get one chapter
Route::post('/chapters/{id}', [ChapterController::class, 'update']); // Update a chapter
Route::delete('/chapters/{id}', [ChapterController::class, 'destroy']); // Delete a chapter
Route::get('/rounds/{round_id}/chapters', [ChapterController::class, 'getChaptersByRound'])->middleware('auth:api');


/*/////////////////////////////// END OF ROUTE CHAPTERS //////////////////////////////////////*/
// VIDEOS GROUPS
Route::get('/videos', [VideoController::class, 'index']); // Get all videos
Route::post('/videos', [VideoController::class, 'store']); // Create a new video
Route::get('/videos/{id}', [VideoController::class, 'show']); // Get one video
Route::post('/videos/{id}', [VideoController::class, 'update']); // Update a video
Route::delete('/videos/{id}', [VideoController::class, 'destroy']); // Delete a video
Route::get('/videos/{chapter_id}/chapters', [VideoController::class, 'getVideosByChapterId'])->middleware('auth:api');



Route::middleware('auth:api')->group(function () {
    Route::post('videos/{video}/save', [VideoController::class, 'saveVideo']);
    Route::get('user/saved-videos', [VideoController::class, 'getSavedVideos']);
    Route::post('videos/{videoId}/unsave', [VideoController::class, 'unsaveVideo']);
    Route::post('videos/{video}/like', [VideoController::class, 'likeVideo']);
    Route::post('videos/{video}/unlike', [VideoController::class, 'unlikeVideo']);
    Route::get('videos/{video}/likes', [VideoController::class, 'getVideoLikes']);
});

/*/////////////////////////////// END OF ROUTE VIDEOS //////////////////////////////////////*/
// CALENDAR GROUPS
Route::get('/groups/{group}/calendars', [CalendarController::class, 'index']);
Route::post('/groups/{group}/calendars', [CalendarController::class, 'store']);
Route::get('/calendars/{id}', [CalendarController::class, 'show']);
Route::post('/calendars/{id}', [CalendarController::class, 'update']);
Route::delete('/calendars/{id}', [CalendarController::class, 'destroy']);
Route::get('/getZoomMeeting/{id}', [CalendarController::class, 'getMeetingZoom']);

/*/////////////////////////////// END OF ROUTE CALENDAR //////////////////////////////////////*/
// COMMENTS GROUPS
Route::middleware('auth:api')->group(function () {
    Route::post('/comments', [CommentsController::class, 'store']);
    Route::get('/videos/{video_id}/comments', [CommentsController::class, 'index']);
    Route::get('/comments/{id}', [CommentsController::class, 'show']);
    Route::delete('/comments/{id}', [CommentsController::class, 'destroy']);
});

/*/////////////////////////////// END OF ROUTE COMMENTS //////////////////////////////////////*/
// REACTIONS GROUPS
Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'comments'
], function () {
    Route::post('{comment}/reactions', [ReactionController::class, 'store']);
    Route::delete('{comment}/reactions', [ReactionController::class, 'destroy']);
});

/*/////////////////////////////// END OF ROUTE REACTIONS //////////////////////////////////////*/
// JOIN AND LEAVE  GROUPS
Route::middleware('auth:api')->group(function () {
    Route::post('groups/{group}/join', [GroupController::class, 'joinGroup']);
    Route::post('groups/{group}/leave', [GroupController::class, 'leaveGroup']);
    Route::get('groups/{groupId}/members', [GroupController::class, 'getGroupMembersWithMeta']);
    Route::get('user/groups', [UserController::class, 'getUserGroups']);
});

/*/////////////////////////////// END OF ROUTE JOIN AND LEAVE //////////////////////////////////////*/
// PAYMENTS GATEWAY
Route::middleware('auth:api')->group(function () {
    Route::post('paymentGatway', [AcceptPaymentGroupController::class, 'store']);
    Route::post('check-subscribtion', [AcceptPaymentGroupController::class, 'checkSubscription']);
    Route::post('renew-subscribtion', [AcceptPaymentGroupController::class, 'renewSubscription']);
    Route::post('delete-subscribtion', [AcceptPaymentGroupController::class, 'delete']);
});

/*/////////////////////////////// END OF ROUTE PAYMENTS GATEWAY //////////////////////////////////////*/
Route::middleware('auth:api')->group(function () {
    Route::post('/progress/complete-video', [ProgressController::class, 'completeVideo']);
    Route::get('/progress/check', [ProgressController::class, 'checkProgress']);
    Route::get('/progress/completion-percentage', [ProgressController::class, 'completionPercentage']);
});
Route::middleware('auth:api')->group(function () {
    Route::post('/video/{video_id}/complete', [ProgressController::class, 'completeVideo']);
    Route::get('/progress/{user_id}/{group_id}', [ProgressController::class, 'getProgress']);
    Route::post('/getLockVideo', [ProgressController::class, 'availableVideo']);
});

Route::middleware('auth:api')->group(function () {
    // Post routes
    Route::get('postsAll', [PostController::class, 'index']);
    Route::post('posts', [PostController::class, 'store']);
    Route::get('posts/{id}', [PostController::class, 'show']);
    Route::post('updatePosts/{id}', [PostController::class, 'update']);
    Route::post('deletePosts/{id}', [PostController::class, 'destroy']);

    // Comment routes
    Route::post('posts/{post}/comments', [PostCommentController::class, 'store']);
    Route::post('comments/{comment}', [PostCommentController::class, 'update']);
    Route::post('deleteComments/{comment}', [PostCommentController::class, 'destroy']);

    // // Reply routes
    Route::post('comments/{comment}/replies', [PostReplyController::class, 'store']);
    Route::post('replies/{reply}', [PostReplyController::class, 'update']);
    Route::post('deleteReplies/{reply}', [PostReplyController::class, 'destroy']);

    // // Like routes
    // Route::post('posts/{post}/likes', [PostLikeController::class, 'store']);
    // Route::delete('posts/{post}/likes', [PostLikeController::class, 'destroy']);
});
