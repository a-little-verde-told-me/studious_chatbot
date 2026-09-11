<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatbotController;

Route::post('/chatbot', [ChatbotController::class, 'respond']);
// throttle:20,1 = max 20 requests per minute per user/IP, so one person
// can't rack up API costs by spamming the widget.
Route::post('/chat', [ChatbotController::class, 'respond'])
    ->middleware('throttle:20,1');
    
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
