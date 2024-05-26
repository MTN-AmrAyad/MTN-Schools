<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ChapterController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\RoundController;
use App\Http\Controllers\UserMetaController;
use App\Http\Controllers\VideoController;

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
Route::get('/rounds/{group_id}/groups', [RoundController::class, 'getChaptersByRound']);

/*/////////////////////////////// END OF ROUTE ROUNDS //////////////////////////////////////*/


// CHAPTERS GROUPS
Route::get('/chapters', [ChapterController::class, 'index']); // Get all chapters
Route::post('/chapters', [ChapterController::class, 'store']); // Create a new chapter
Route::get('/chapters/{id}', [ChapterController::class, 'show']); // Get one chapter
Route::post('/chapters/{id}', [ChapterController::class, 'update']); // Update a chapter
Route::delete('/chapters/{id}', [ChapterController::class, 'destroy']); // Delete a chapter
Route::get('/rounds/{round_id}/chapters', [ChapterController::class, 'getChaptersByRound']);


/*/////////////////////////////// END OF ROUTE CHAPTERS //////////////////////////////////////*/
// VIDEOS GROUPS
Route::get('/videos', [VideoController::class, 'index']); // Get all videos
Route::post('/videos', [VideoController::class, 'store']); // Create a new video
Route::get('/videos/{id}', [VideoController::class, 'show']); // Get one video
Route::post('/videos/{id}', [VideoController::class, 'update']); // Update a video
Route::delete('/videos/{id}', [VideoController::class, 'destroy']); // Delete a video
Route::get('/videos/{chapter_id}/chapters', [VideoController::class, 'getVideosByChapterId']);

/*/////////////////////////////// END OF ROUTE VIDEOS //////////////////////////////////////*/
// CALENDAR GROUPS
Route::get('/groups/{group}/calendars', [CalendarController::class, 'index']);
Route::post('/groups/{group}/calendars', [CalendarController::class, 'store']);
Route::get('/calendars/{id}', [CalendarController::class, 'show']);
Route::post('/calendars/{id}', [CalendarController::class, 'update']);
Route::delete('/calendars/{id}', [CalendarController::class, 'destroy']);

/*/////////////////////////////// END OF ROUTE CALENDAR //////////////////////////////////////*/
