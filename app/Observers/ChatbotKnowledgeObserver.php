<?php

namespace App\Observers;

use App\Models\ChatbotKnowledge;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotKnowledgeObserver
{
    public function saving(ChatbotKnowledge $chatbotKnowledge)
    {
        // Only generate embedding if the question or answer changed, or if it's currently null
        if ($chatbotKnowledge->isDirty('question') || $chatbotKnowledge->isDirty('answer') || empty($chatbotKnowledge->embedding)) {
            $textToEmbed = "Q: " . $chatbotKnowledge->question . "\nA: " . $chatbotKnowledge->answer;
            
            $apiKey = env('AI_API_KEY') ?? config('services.ai.key');
            $model = 'gemini-embedding-001';

            try {
                $response = Http::withoutVerifying()
                    ->withHeaders(['x-goog-api-key' => $apiKey])
                    ->timeout(30)
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:embedContent", [
                        'model' => "models/{$model}",
                        'content' => [
                            'parts' => [['text' => $textToEmbed]]
                        ]
                    ]);

                if ($response->successful()) {
                    $vector = $response->json('embedding.values');
                    $chatbotKnowledge->embedding = json_encode($vector);
                } else {
                    Log::error('Gemini Embedding API Error: ' . $response->body());
                }
            } catch (\Throwable $e) {
                Log::error('Failed to auto-generate embedding: ' . $e->getMessage());
            }
        }
    }
}