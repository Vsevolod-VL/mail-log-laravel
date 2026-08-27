<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use VsevolodVL\MailLogLaravel\Http\Controllers\AttachmentController;
use VsevolodVL\MailLogLaravel\Http\Controllers\EventController;
use VsevolodVL\MailLogLaravel\Http\Controllers\GroupController;
use VsevolodVL\MailLogLaravel\Http\Controllers\TestSendController;

Route::prefix((string) config('mail-log.ui.path', 'mail-log'))
    ->middleware(config('mail-log.ui.middleware', ['web']))
    ->name('mail-log.')
    ->group(function (): void {
        Route::get('/', [GroupController::class, 'index'])->name('index');
        Route::post('/test-send', [TestSendController::class, 'store'])->name('test-send');
        Route::get('/{group}', [GroupController::class, 'show'])->name('show');
        Route::delete('/{group}', [GroupController::class, 'destroy'])->name('destroy');
        Route::get('/{group}/attachments/{media}', [AttachmentController::class, 'show'])->name('attachment');
        Route::get('/{group}/events/{event}', [EventController::class, 'show'])->name('event');
    });
