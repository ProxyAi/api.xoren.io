<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::pattern('username', '^[a-zA-Z0-9._-]+$');

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::get('/twitch/{username}', \App\Http\Controllers\Api\Twitch\User\Controller::class);
