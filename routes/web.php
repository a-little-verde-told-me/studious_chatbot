<?php

use Illuminate\Support\Facades\Route;

// test fallback logic
// use Illuminate\Support\Facades\Http;
// use Illuminate\Http\Request;
// use App\Http\Controllers\ChatbotController;

// Route::get('/test-fallback-simulation', function () {
//     // 1. Tell Laravel to fake external network requests
//     Http::fake([
//         // Force the primary model to fail with a 429 Rate Limit error
//         'generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash*' => Http::response(['error' => ['code' => 429]], 429),
        
//         // Force the fallback model to succeed with a mock reply
//         'generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash*' => Http::response([
//             'candidates' => [
//                 ['content' => ['parts' => [['text' => 'Success! Fallback model took over.']]]]
//             ]
//         ], 200)
//     ]);

//     // 2. Simulate a request to your controller
//     $controller = new ChatbotController();
//     $request = new Request(['message' => 'Hello Leon']);

//     // 3. Run the non-streaming respond method to see the result
//     $response = $controller->respond($request);

//     return $response;
// });

Route::get('/', function () {
    return view('welcome');
});
