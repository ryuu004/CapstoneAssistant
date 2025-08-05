<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GeminiController;
use App\Http\Controllers\FileUploadController;

Route::get('/{conversationId?}', function ($conversationId = null) {
    return view('assistant', ['conversationId' => $conversationId]);
})->where('conversationId', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');

Route::post('/ask-gemini', [GeminiController::class, 'ask']);

Route::post('/upload-file', [FileUploadController::class, 'upload']);

// Conversation history routes
Route::post('/conversations/new', [GeminiController::class, 'newConversation']);
Route::get('/conversations', [GeminiController::class, 'getConversations']);
Route::get('/conversations/{conversationId}/messages', [GeminiController::class, 'getMessages']);
Route::delete('/conversations/{conversationId}', [GeminiController::class, 'deleteConversation']);
Route::post('/conversations/title', [GeminiController::class, 'updateConversationTitle']);
