<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

// Only 'api' middleware — NO 'web' here
Route::middleware('api')->group(function () {
    Route::post('/conversations', [ChatController::class, 'storeConversation']);
    Route::get('/conversations', [ChatController::class, 'index']);
    Route::get('/conversations/{conversationId}/messages', [ChatController::class, 'messages']);
    Route::post('/conversations/{conversationId}/messages', [ChatController::class, 'storeMessage']);
    Route::post('/conversations/{conversationId}/typing', [ChatController::class, 'typing']);
    Route::delete('/conversations/{conversationId}', [ChatController::class, 'destroy']);
});

Route::middleware('api')->group(function () {
    Route::post('/uploads', [UploadController::class, 'store']);
});
