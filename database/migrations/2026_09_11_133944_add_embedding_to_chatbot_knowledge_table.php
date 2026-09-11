<?php

namespace App\Observers;

use App\Models\ChatbotKnowledge;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotKnowledgeObserver
{
    public function creating(ChatbotKnowledge $chatbotKnowledge)
    {
        // Combine question and answer to give the embedding full context
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
                // Automatically assign the JSON-encoded vector to the embedding column
                $chatbotKnowledge->embedding = json_encode($vector);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to auto-generate embedding on create: ' . $e->getMessage());
        }
    }
}