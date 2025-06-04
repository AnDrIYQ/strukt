<?php

use App\Http\Controllers\Api\StructureController;
use App\Http\Controllers\Api\UserSettingsApiController;
use App\Http\Controllers\UserAvatarController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EndPointExecutorController;
use App\Http\Controllers\Api\SecureFileController;
use Illuminate\Support\Facades\Broadcast;

// Public routes
Broadcast::routes();
Route::get('/users/public', [EndPointExecutorController::class, 'publicUsers']);

// Auth routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
});
Route::get('/user/avatar/signed/{user}', [UserAvatarController::class, 'showSigned'])
    ->name('user.avatar.signed')
    ->middleware('signed');
Route::middleware('auth:sanctum')->post('/user/settings', [UserSettingsApiController::class, 'update']);

// Collections routes
Route::get('/structure', [StructureController::class, 'index']);
Route::prefix('collections')->middleware('optional.auth')->group(function () {
    Route::match(['get', 'post'], '{collection}/{path}', [EndPointExecutorController::class, 'handle']);
});
Route::get('/uploads/{collection}/{field}/{entry}/{index?}', [SecureFileController::class, 'show'])
    ->name('secure-file')
    ->middleware('signed');;
