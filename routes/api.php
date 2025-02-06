<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LanguageController;
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
Route::prefix('moodle')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login'); // Đăng nhập
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']); // Quên mật khẩu
    Route::post('/reset-password', [AuthController::class, 'resetPassword']); // Reset mật khẩu

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']); // Đăng xuất
        Route::post('/change-password', [AuthController::class, 'changePassword']); // Đổi mật khẩu
        Route::put('/profile', [ProfileController::class, 'updateProfile']); // Cập nhật hồ sơ
        Route::patch('/language', [LanguageController::class, 'updateLanguage']); // Thay đổi ngôn ngữ
    });
});