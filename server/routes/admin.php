<?php

use App\Http\Controllers\AdminAccountController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\GenerationModeController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SiteContentController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\TemplateMediaController;
use App\Http\Controllers\WalletController;
use App\Http\Middleware\EnsureAdminPermission;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AdminController::class, 'loginPage'])->name('admin.login');
        Route::post('/login', [AdminController::class, 'login'])->middleware('throttle:6,1');
    });
    Route::middleware(['auth:admin', EnsureAdminPermission::class.':console'])->group(function () {
        Route::middleware(EnsureAdminPermission::class.':templates.manage')->group(function () {
            Route::post('/template-media', [TemplateMediaController::class, 'store'])->middleware('throttle:20,1');
            Route::get('/template-media/{media}', [TemplateMediaController::class, 'preview']);
            Route::get('/templates', [TemplateController::class, 'adminIndex']);
            Route::post('/templates', [TemplateController::class, 'save']);
            Route::put('/templates/{template}', [TemplateController::class, 'save']);
            Route::post('/templates/{template}/status', [TemplateController::class, 'status']);
        });
        Route::get('/wallet', [WalletController::class, 'adminIndex'])->middleware(EnsureAdminPermission::class.':wallet.manage');
        Route::post('/wallet/adjustments', [WalletController::class, 'adjust'])->middleware([EnsureAdminPermission::class.':wallet.manage', 'throttle:20,1']);
        Route::middleware(EnsureAdminPermission::class.':support.manage')->group(function () {
            Route::get('/support', [SupportController::class, 'adminIndex']);
            Route::get('/support/{ticket}', [SupportController::class, 'adminShow']);
            Route::post('/support/{ticket}/messages', [SupportController::class, 'adminReply'])->middleware('throttle:30,1');
        });
        Route::put('/users/{user}/status', [AdminAccountController::class, 'userStatus'])->middleware(EnsureAdminPermission::class.':users.manage');
        Route::get('/accounts', [AdminAccountController::class, 'accounts'])->middleware(EnsureAdminPermission::class.':accounts.manage');
        Route::put('/accounts/{account}', [AdminAccountController::class, 'update'])->middleware(EnsureAdminPermission::class.':accounts.manage');
        Route::middleware(EnsureAdminPermission::class.':content.manage')->group(function () {
            Route::get('/content', [SiteContentController::class, 'adminIndex']);
            Route::post('/content', [SiteContentController::class, 'save']);
            Route::put('/content/{content}', [SiteContentController::class, 'save']);
            Route::post('/content/{content}/publish', [SiteContentController::class, 'publish']);
            Route::post('/content/{content}/withdraw', [SiteContentController::class, 'withdraw']);
        });
        Route::get('/generation-modes', [GenerationModeController::class, 'index'])->middleware(EnsureAdminPermission::class.':generation.manage');
        Route::put('/generation-modes/{kind}', [GenerationModeController::class, 'update'])->middleware(EnsureAdminPermission::class.':generation.manage');
        Route::get('/reports', [ReportController::class, 'index'])->middleware(EnsureAdminPermission::class.':reports.view');
        Route::get('/', [AdminController::class, 'dashboard']);
        Route::post('/logout', [AdminController::class, 'logout']);
        Route::get('/users', [AdminAccountController::class, 'users'])->middleware(EnsureAdminPermission::class.':users.view');
        Route::get('/tasks', [AdminController::class, 'tasks'])->middleware(EnsureAdminPermission::class.':tasks.view');
        Route::get('/tasks/{task}', [ModerationController::class, 'show'])->middleware(EnsureAdminPermission::class.':tasks.view');
        Route::get('/tasks/{task}/input', [ModerationController::class, 'input'])->middleware(EnsureAdminPermission::class.':tasks.inputs');
        Route::post('/tasks/{task}/review', [ModerationController::class, 'review'])->middleware([EnsureAdminPermission::class.':tasks.review', 'throttle:30,1']);
        Route::get('/audit', [AdminController::class, 'audit'])->middleware(EnsureAdminPermission::class.':audit.view');
    });
});
