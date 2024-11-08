<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;

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

Route::post('register', [UserController::class, 'createUsers']);// đăng kí

Route::post('/auth/login', [AuthController::class, 'login']); //đăng nhập
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']); //đăng xuất

Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']); //quên mật khẩu
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']); //reset mật khẩu