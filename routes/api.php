<?php

use App\Http\Controllers\API\Auth\AdminController;
use App\Http\Controllers\API\Auth\SignupController;
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

Route::apiResource('users', AdminController::class);



# Auth routes
Route::prefix('auth')->group(function () {
    Route::prefix('admin')->group(function () {
        Route::post('/login', [AdminController::class, 'login']);
        Route::post('/register', [AdminController::class, 'register']);
        Route::post('/logout', [AdminController::class, 'logout']);
        Route::post('/refresh', [AdminController::class, 'refresh']);
        Route::post('/{admin}/status', [AdminController::class, 'changeStatus']);
    });
    Route::prefix('user')->group(function () {
        Route::post('/login', [SignupController::class, 'login']);
        Route::post('/register', [SignupController::class, 'register']);
        Route::post('/resend-confirmation-code', [SignupController::class, 'resendConfirmationCode']);
        Route::post('/confirm-registration', [SignupController::class, 'confirmRegistration']);
        Route::post('/change-password', [SignupController::class, 'changePassword']);
    });
});


