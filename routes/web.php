<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log; // Add this line
use App\Http\Controllers\GeminiController;
use App\Http\Controllers\FileUploadController;

Route::get('/{conversationId?}', function ($conversationId = null) {
    $professorAvatarUrl = asset('images/professor_avatar.jpg');
    return view('assistant', compact('conversationId', 'professorAvatarUrl'));
})->where('conversationId', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');

Route::post('/ask-gemini', [GeminiController::class, 'ask']);

Route::post('/upload-file', [FileUploadController::class, 'upload']);

// Conversation history routes
Route::post('/conversations/new', [GeminiController::class, 'newConversation']);
Route::get('/conversations', [GeminiController::class, 'getConversations']);
Route::get('/conversations/{conversationId}/messages', [GeminiController::class, 'getMessages']);
Route::delete('/conversations/{conversationId}', [GeminiController::class, 'deleteConversation']);
Route::post('/conversations/title', [GeminiController::class, 'updateConversationTitle']);
Route::post('/validate-api-key', [GeminiController::class, 'validateApiKey']);
Route::get('/debug-config', function () {
    return [
        'SESSION_DRIVER' => config('session.driver'),
        'SESSION_CONNECTION' => config('session.connection'),
        'DB_CONNECTION' => config('database.default'),
        'DB_HOST' => config('database.connections.pgsql.host'),
        'DB_PORT' => config('database.connections.pgsql.port'),
        'DB_DATABASE' => config('database.connections.pgsql.database'),
        'DB_USERNAME' => config('database.connections.pgsql.username'),
        'DB_PASSWORD' => config('database.connections.pgsql.password') ? '******' : 'null',
        'DB_SSLMODE' => config('database.connections.pgsql.sslmode'),
    ];
});

// CSP Reporting Endpoint
Route::post('/csp-report', [App\Http\Controllers\CSPReportController::class, 'report']);

