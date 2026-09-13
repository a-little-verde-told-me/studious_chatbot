<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatbotController;

// Stream route called by your widget
Route::middleware(['throttle:chatbot'])->post('/chatbot/stream', [ChatbotController::class, 'streamRespond']);

// Standard JSON response route
Route::post('/chatbot', [ChatbotController::class, 'respond']);

Route::post('/chat', [ChatbotController::class, 'respond'])
    ->middleware('throttle:20,1');
    
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');