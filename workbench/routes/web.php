<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Debug: check if mail-log routes are loaded
Route::get('/mail-log-test', function () {
    return response()->json([
        'message' => 'Mail-Log routes should be loading...',
        'mail_log_path' => config('mail-log.ui.path'),
    ]);
});

