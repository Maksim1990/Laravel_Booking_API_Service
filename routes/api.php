<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\SystemController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('version', [SystemController::class, 'version']);

Route::apiResource('users', UserController::class);
Route::post('users/{user}/status',[UserController::class, 'changeStatus']);

# Auth routes
Route::post('auth/login',[AuthController::class, 'login']);
Route::post('auth/register',[AuthController::class, 'register']);
Route::post('auth/logout',[AuthController::class, 'logout'])->middleware('auth:api');
Route::post('auth/refresh',[AuthController::class, 'refresh'])->middleware('auth:api');
