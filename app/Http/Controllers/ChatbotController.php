<?php

namespace App\Http\Controllers;

use App\Models\ChatbotKnowledge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatbotController extends Controller
{
    public function respond(Request $request)
    {
        set_time_limit(120);

        $validated = $request->validate([
            'message'             => 'required|string|max:1000',
            'history'             => 'array|max:20',
            'history.*.role'      => 'in:user,assistant',
            'history.*.content'   => 'string|max:2000',
        ]);

        $userMessage = trim($validated['message']);
        $history     = $validated['history'] ?? [];

        $relevantArticles = $this->retrieveRelevantKnowledge($userMessage);

        $topMatch = $relevantArticles->first();
        
        $similarity = $topMatch->similarity ?? 'NO_SIMILARITY_PROPERTY';

        // Temporary Debug Log
        Log::info("Chatbot Debug - Message: '{$userMessage}' | Top Similarity: {$similarity}");

        // Fix: Triggers log if no match, if similarity property doesn't exist (fallback), or if similarity is under 0.40
        $hasLowSimilarity = !$topMatch || !isset($topMatch->similarity) || $topMatch->similarity < 0.40;

        if (count(explode(' ', trim($userMessage))) > 1) {
                $this->logUnhandledQuery($userMessage, $request);
            }
    

        $systemPrompt = $this->buildSystemPrompt($relevantArticles);

        try {
            $reply = $this->callAiApi($systemPrompt, $history, $userMessage);
        } catch (\Throwable $e) {
            Log::warning('Leon chatbot API call failed: ' . $e->getMessage());

            return response()->json([
                'reply'    => "I'm having trouble connecting to my knowledge base right now due to a network delay. Please come back and try again later, or open a Helpdesk ticket.",
                'fallback' => true,
            ]);
        }

        return response()->json(['reply' => $reply]);
    }

    public function streamRespond(Request $request)
    {
        set_time_limit(120);

        $validated = $request->validate([
            'message'             => 'required|string|max:1000',
            'history'             => 'array|max:20',
            'history.*.role'      => 'in:user,assistant',
            'history.*.content'   => 'string|max:2000',
        ]);

        $userMessage = trim($validated['message']);
        $history     = $validated['history'] ?? [];

        $relevantArticles = $this->retrieveRelevantKnowledge($userMessage);

        $topMatch = $relevantArticles->first();

        $similarity = $topMatch->similarity ?? 'NO_SIMILARITY_PROPERTY';

        // Temporary Debug Log
        Log::info("Chatbot Debug - Message: '{$userMessage}' | Top Similarity: {$similarity}");

        // Fix: Triggers log if no match, if similarity property doesn't exist (fallback), or if similarity is under 0.40
        $hasLowSimilarity = !$topMatch || !isset($topMatch->similarity) || $topMatch->similarity < 0.40;

        if (count(explode(' ', trim($userMessage))) > 1) {
                $this->logUnhandledQuery($userMessage, $request);
            }
        

        $systemPrompt = $this->buildSystemPrompt($relevantArticles);

        $contents = [];
        foreach ($history as $turn) {
            $contents[] = [
                'role'  => $turn['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $turn['content']]],
            ];
        }
        $contents[] = [
            'role'  => 'user',
            'parts' => [['text' => $userMessage]],
        ];

        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => $systemPrompt]]
            ],
            'contents' => $contents,
            'generationConfig' => [
                'maxOutputTokens' => 1500,
                'temperature'     => 0.3,
            ]
        ];

        $apiKey = env('AI_API_KEY') ?? config('services.ai.key');
        $primaryModel = config('services.ai.model', 'gemini-3.6-flash');
        $fallbackModel = 'gemini-3.5-flash';

        return new StreamedResponse(function () use ($primaryModel, $fallbackModel, $apiKey, $payload) {
            try {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$primaryModel}:streamGenerateContent?alt=sse";

                $http = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'x-goog-api-key' => $apiKey,
                ])->timeout(65);

                if (app()->environment('local')) {
                    $http->withoutVerifying();
                }

                $response = $http->send('POST', $url, [
                    'json' => $payload,
                    'stream' => true,
                ]);

                if ($response->status() === 429) {
                    Log::warning("Gemini streaming primary model ({$primaryModel}) hit rate limit (429). Retrying with fallback ({$fallbackModel}).");
                    
                    sleep(2);

                    $fallbackUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$fallbackModel}:streamGenerateContent?alt=sse";
                    
                    $fallbackHttp = Http::withHeaders([
                        'Content-Type' => 'application/json',
                        'x-goog-api-key' => $apiKey,
                    ])->timeout(65);

                    if (app()->environment('local')) {
                        $fallbackHttp->withoutVerifying();
                    }

                    $response = $fallbackHttp->send('POST', $fallbackUrl, [
                        'json' => $payload,
                        'stream' => true,
                    ]);
                }

                if ($response->failed()) {
                    throw new \RuntimeException('Gemini stream API returned status ' . $response->status());
                }

                $body = $response->toPsrResponse()->getBody();

                while (!$body->eof()) {
                    $chunk = $body->read(1024);
                    echo $chunk;
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }
            } catch (\Throwable $e) {
                Log::warning('Leon chatbot stream connection failed: ' . $e->getMessage());

                $fallbackText = "I'm having trouble connecting right now due to a network issue. Please come back and try again later!";
                $fallbackData = json_encode([
                    "candidates" => [
                        [
                            "content" => [
                                "parts" => [
                                    ["text" => $fallbackText]
                                ]
                            ]
                        ]
                    ]
                ]);

                echo "data: " . $fallbackData . "\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function retrieveRelevantKnowledge(string $userMessage, int $limit = 4)
    {
        $queryEmbedding = $this->getEmbedding($userMessage);

        if (!$queryEmbedding) {
            return ChatbotKnowledge::query()->limit($limit)->get(['question', 'answer', 'category']);
        }

        $articles = ChatbotKnowledge::whereNotNull('embedding')->get(['question', 'answer', 'category', 'embedding']);

        if ($articles->isEmpty()) {
            return ChatbotKnowledge::query()->limit($limit)->get(['question', 'answer', 'category']);
        }

        $scoredArticles = $articles->map(function ($article) use ($queryEmbedding) {
            $storedEmbedding = is_string($article->embedding) ? json_decode($article->embedding, true) : $article->embedding;
            $article->similarity = $this->cosineSimilarity($queryEmbedding, $storedEmbedding ?? []);
            return $article;
        });

        return $scoredArticles->sortByDesc('similarity')->take($limit);
    }

    private function getEmbedding(string $text): ?array
    {
        $apiKey = env('AI_API_KEY') ?? config('services.ai.key');
        $model = 'gemini-embedding-001';

        try {
            $http = Http::withHeaders(['x-goog-api-key' => $apiKey])->timeout(65);

            if (app()->environment('local')) {
                $http->withoutVerifying();
            }

            $response = $http->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:embedContent", [
                'model' => "models/{$model}",
                'content' => [
                    'parts' => [['text' => $text]]
                ]
            ]);

            if ($response->successful()) {
                return $response->json('embedding.values');
            }
        } catch (\Throwable $e) {
            Log::error('Embedding API connection timeout or error: ' . $e->getMessage());
        }

        return null;
    }

    private function cosineSimilarity(array $vecA, array $vecB): float
    {
        if (empty($vecA) || empty($vecB) || count($vecA) !== count($vecB)) {
            return 0.0;
        }

        $dotProduct = 0;
        $normA = 0;
        $normB = 0;

        for ($i = 0; $i < count($vecA); $i++) {
            $dotProduct += $vecA[$i] * $vecB[$i];
            $normA += $vecA[$i] ** 2;
            $normB += $vecB[$i] ** 2;
        }

        $denominator = sqrt($normA) * sqrt($normB);
        return $denominator == 0 ? 0.0 : $dotProduct / $denominator;
    }

    private function buildSystemPrompt($articles): string
    {
        $context = $articles->isEmpty()
            ? 'No matching articles were found in the knowledge base.'
            : $articles->map(fn ($a) => "Q: {$a->question}\nA: {$a->answer}")->implode("\n\n");

        return <<<PROMPT
You are Leon, the friendly lion mascot and official AI assistant for PSU-StudiOUS (Pangasinan State University Open University Systems portal).

Provide clear, thorough, and well-detailed answers grounded ONLY in the knowledge base context provided below. Whenever applicable, structure your responses using distinct paragraphs, bullet points, or step-by-step lists to make instructions easy to follow. Also if needed provide links to relevant sections of the PSU-StudiOUS portal or other resources for further guidance. Avoid repeating the same information multiple times in a single response.

Strict Constraints:

* If the user sends a casual greeting (e.g., "hi", "hello", "hey", "good morning") without asking a specific question, ignore the knowledge base context and respond warmly as Leon, briefly welcoming them and asking how you can help with their PSU-StudiOUS concerns today.
* Answer ONLY using the knowledge base context provided below. Never guess, extrapolate, or invent fees, dates, or university policies.
* Do not include greetings or re-introductions in your replies, as the user has already been greeted when opening the assistant. Go straight to answering the question.
* If the provided context does not contain enough information to answer fully, state clearly that you do not have that specific information yet and kindly direct the student to submit a Helpdesk ticket.
* Never ask for, request, or reference a specific student's personal application status, payment details, or private account information—you have no access to live user records.
* Always redirect account-specific, personal status, or private payment inquiries directly to the student portal Application Tracker or the Helpdesk.

KNOWLEDGE BASE CONTEXT:
{$context}
PROMPT;
    }

    private function callAiApi(string $systemPrompt, array $history, string $userMessage): string
    {
        $contents = [];

        foreach ($history as $turn) {
            $contents[] = [
                'role'  => $turn['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $turn['content']]],
            ];
        }

        $contents[] = [
            'role'  => 'user',
            'parts' => [['text' => $userMessage]],
        ];

        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => $systemPrompt]]
            ],
            'contents' => $contents,
            'generationConfig' => [
                'maxOutputTokens' => 1500,
                'temperature'     => 0.3,
            ]
        ];

        $apiKey = env('AI_API_KEY') ?? config('services.ai.key');
        
        $primaryModel  = config('services.ai.model', 'gemini-3.6-flash');
        $fallbackModel = 'gemini-3.5-flash';

        $response = $this->sendGeminiPost($primaryModel, $apiKey, $payload);

        if ($response->status() === 429) {
            Log::warning("Gemini primary model ({$primaryModel}) hit rate limit (429). Retrying with fallback ({$fallbackModel}).");
            $response = $this->sendGeminiPost($fallbackModel, $apiKey, $payload);
        }

        if ($response->failed()) {
            throw new \RuntimeException('Gemini API returned status ' . $response->status() . ': ' . $response->body());
        }

        return $response->json('candidates.0.content.parts.0.text')
            ?? "I'm not sure how to answer that yet — please try rephrasing, or open a Helpdesk ticket.";
    }

    private function sendGeminiPost(string $model, string $apiKey, array $payload)
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        $http = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-goog-api-key' => $apiKey
        ])->timeout(65);

        if (app()->environment('local')) {
            $http->withoutVerifying();
        }

        return $http->post($url, $payload);
    }
    
    private function logUnhandledQuery(string $message, Request $request): void
    {
        try {
            \App\Models\UnhandledChatbotQuery::forceCreate([
                'user_message' => $message,
                'ip_address'   => $request->ip(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed logging unhandled query to DB: ' . $e->getMessage());
        }
    }
    
}