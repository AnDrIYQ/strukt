<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EndPointController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\TranslationController;
use App\Http\Controllers\UserAvatarController;
use App\Http\Controllers\UserSettingsController;

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('public_only')->group(function() {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/settings', [UserSettingsController::class, 'edit']);
    Route::post('/settings', [UserSettingsController::class, 'update']);
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});

Route::get('/user/avatar', [UserAvatarController::class, 'show'])->middleware('auth');
Route::get('/user/avatar/{user}', [UserAvatarController::class, 'showPublic'])->name('avatar.public');

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/collections', [CollectionController::class, 'index'])->name('collections.index');
    Route::get('/collections/create', [CollectionController::class, 'create'])->name('collections.create');
    Route::post('/collections', [CollectionController::class, 'store'])->name('collections.store');
    Route::post('/collections/mass-delete', [CollectionController::class, 'destroyMany'])->name('collections.destroyMany');
    Route::delete('/collections/{collection}', [CollectionController::class, 'destroy'])->name('collections.destroy');
});

Route::prefix('translations')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [TranslationController::class, 'index'])->name('translations.index');
    Route::post('/', [TranslationController::class, 'store'])->name('translations.store');
    Route::put('/{translation}', [TranslationController::class, 'update'])->name('translations.update');
    Route::delete('/{translation}', [TranslationController::class, 'destroy'])->name('translations.destroy');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/collections/{collection}/endpoints/edit/{id}', [EndPointController::class, 'edit'])->name('collections.endpoints.edit');
    Route::get('/collections/{collection}/endpoints', [EndPointController::class, 'index'])->name('collections.endpoints.index');
    Route::post('/collections/{collection}/endpoints', [EndPointController::class, 'store'])->name('collections.endpoints.store');
    Route::delete('/collections/{collection}/endpoints/{id}', [EndPointController::class, 'destroy'])->name('collections.endpoints.destroy');
    Route::post('/collections/{collection}/endpoints/mass-delete', [EndPointController::class, 'destroyMany'])->name('collections.endpoints.destroyMany');
    Route::get('/collections/{collection}/endpoints/create', [EndPointController::class, 'create'])->name('collections.endpoints.create');
    Route::put('/collections/{collection}/endpoints/{id}', [EndPointController::class, 'update'])
        ->name('collections.endpoints.update');
});

// Sandbox
Route::get('/sandbox', fn () => Inertia::render('Sandbox/Index'))->middleware(['auth', 'admin']);
