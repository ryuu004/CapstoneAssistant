<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log; // Add this line
use App\Http\Controllers\GeminiController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\AuthController;

Route::middleware(['auth'])->group(function () {
    Route::get('/assistant/{conversationId?}', [GeminiController::class, 'show'])
         ->where('conversationId', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
         ->name('assistant.show');
    Route::get('/assistant/new', [GeminiController::class, 'newConversation'])->name('assistant.new');

    Route::post('/ask-gemini/{conversationId?}', [GeminiController::class, 'ask'])
         ->where('conversationId', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');
    Route::post('/upload-file', [FileUploadController::class, 'upload']);

    Route::delete('/conversations/{conversationId}', [GeminiController::class, 'deleteConversation'])->name('conversations.delete');

    Route::get('/assistant/{conversation}/messages', [GeminiController::class, 'messages']);
    Route::post('/validate-api-key', [GeminiController::class, 'validateApiKey']);
});

// CSP Reporting Endpoint
Route::post('/csp-report', [App\Http\Controllers\CSPReportController::class, 'report']);

Route::get('/', function () {
    return redirect()->route('assistant.show');
});

Route::get('/auth', function () {
    return redirect()->route('login');
})->name('auth');

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
