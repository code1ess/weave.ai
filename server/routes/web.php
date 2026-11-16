<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PreferenceController;
use App\Http\Controllers\SiteContentController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\TemplateMediaController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::post('/preferences', [PreferenceController::class, 'update'])->middleware('throttle:60,1');
Route::get('/templates', [TemplateController::class, 'index']);
Route::get('/templates/{template}/cover', [TemplateMediaController::class, 'cover']);
Route::get('/templates/{template}', [TemplateController::class, 'show']);
Route::get('/', [SiteContentController::class, 'home']);
Route::get('/help', [SiteContentController::class, 'help']);
Route::get('/help/{slug}', [SiteContentController::class, 'article']);
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
});
Route::middleware(['auth:web', 'auth.session'])->group(function () {
    Route::get('/settings', [AccountController::class, 'index']);
    Route::put('/settings', [AccountController::class, 'update']);
    Route::put('/settings/password', [AccountController::class, 'password'])->middleware('throttle:6,1');
    Route::get('/support', [SupportController::class, 'index']);
    Route::post('/support', [SupportController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/support/{ticket}', [SupportController::class, 'show']);
    Route::post('/support/{ticket}/messages', [SupportController::class, 'reply'])->middleware('throttle:20,1');
    Route::post('/tasks/{task}/cancel', [TaskController::class, 'cancel'])->middleware('throttle:30,1');
    Route::get('/works', [TaskController::class, 'history']);
    Route::get('/works/{task}', [TaskController::class, 'detail']);
    Route::get('/assets', [AssetController::class, 'index']);
    Route::delete('/assets/{asset}', [AssetController::class, 'destroy'])->middleware('throttle:20,1');
    Route::get('/wallet', [WalletController::class, 'index']);
    Route::post('/assets', [AssetController::class, 'store'])->middleware('throttle:20,1');
    Route::get('/assets/{asset}', [AssetController::class, 'show']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/studio', [TaskController::class, 'index'])->name('studio');
    Route::post('/tasks', [TaskController::class, 'store'])->middleware('throttle:30,1');
    Route::get('/tasks/{task}', [TaskController::class, 'show']);
});

require __DIR__.'/admin.php';
