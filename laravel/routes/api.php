<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Dns\Cloudflare\Domain\Check\Controller as DnsCheckController;
use App\Http\Controllers\Api\Dns\Cloudflare\Domain\Update\Controller as DnsUpdateController;


Route::pattern('username', '^[a-zA-Z0-9._-]+$');

Route::get('/checkip', \App\Http\Controllers\Api\Check\Ip\Controller::class);

// Basic Auth protected DNS API endpoint
Route::middleware(['basic.auth'])->group(function () {
    Route::get('/dns/{domain}/checkip', DnsCheckController::class)->where('domain', '^[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}$');
    Route::post('/dns/{domain}/checkip', DnsUpdateController::class)->where('domain', '^[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}$');
});

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
