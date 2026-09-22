<?php

namespace App\Http\Controllers;

use App\Models\ChatbotKnowledge;
use App\Models\UnhandledChatbotQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatbotController extends Controller
{
    public function respond(Request $request)
    {
        set_time_limit(120);

        $prepared = $this->prepareChatPayload($request);

        try {
            $reply = $this->callAiApi($prepared['systemPrompt'], $prepared['history'],$prepared['userMessage']);
        } catch (\Throwable $e) {
            Log::warning('Leon chatbot API call failed: ' . $e->getMessage());

            return response()->json([
                'reply'    => "I'm having trouble connecting to my knowledge base right now due to a network delay. Please come back and try again later, or go to Knowledge-base page or open a Helpdesk ticket.",
                'fallback' => true,
            ]);
        }

        return response()->json(['reply' => $reply]);
    }

    public function streamRespond(Request $request)
    {
        set_time_limit(120);

        $prepared = $this->prepareChatPayload($request);
        $payload  =$prepared['payload'];

        $apiKey       = env('AI_API_KEY') ?? config('services.ai.key');
        $primaryModel = config('services.ai.model', 'gemini-3.6-flash');$fallbackModel = 'gemini-3.5-flash';

        return new StreamedResponse(function () use ($primaryModel,$fallbackModel, $apiKey,$payload) {
            try {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$primaryModel}:streamGenerateContent?alt=sse";

                $http = Http::withHeaders([
                    'Content-Type'   => 'application/json',
                    'x-goog-api-key' => $apiKey,
                ])->timeout(65);

                if (app()->environment('local')) {
                    $http->withoutVerifying();
                }

                $response = $http->send('POST',$url, [
                    'json'   => $payload,
                    'stream' => true,
                ]);

                if ($response->status() === 429) {
                    Log::warning("Gemini streaming primary model ({$primaryModel}) hit rate limit (429). Retrying with fallback ({$fallbackModel}).");
                    
                    sleep(2);

                    $fallbackUrl  = "https://generativelanguage.googleapis.com/v1beta/models/{$fallbackModel}:streamGenerateContent?alt=sse";
                    $fallbackHttp = Http::withHeaders([
                        'Content-Type'   => 'application/json',
                        'x-goog-api-key' => $apiKey,
                    ])->timeout(65);

                    if (app()->environment('local')) {
                        $fallbackHttp->withoutVerifying();
                    }

                    $response = $fallbackHttp->send('POST',$fallbackUrl, [
                        'json'   => $payload,
                        'stream' => true,
                    ]);
                }

                if ($response->failed()) {
                    throw new \RuntimeException('Gemini stream API returned status ' . $response->status());
                }

                $body =$response->toPsrResponse()->getBody();

                while (!$body->eof()) {
                    $chunk =$body->read(1024);
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
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Shared pipeline logic for validation, retrieval, logging, and payload building.
     */
    private function prepareChatPayload(Request $request): array
    {
        $validated =$request->validate([
            'message'           => 'required|string|max:1000',
            'history'           => 'array|max:20',
            'history.*.role'    => 'in:user,assistant',
            'history.*.content' => 'string|max:2000',
        ]);

        $userMessage = trim($validated['message']);
        $history     =$validated['history'] ?? [];

        $relevantArticles = $this->retrieveRelevantKnowledge($userMessage);

        $topMatch      = $relevantArticles->first();$topSimilarity = isset($topMatch->similarity) ? (float)$topMatch->similarity : 0.0;

        Log::info("Chatbot Log Check - Raw Message: '{$userMessage}' | Similarity: {$topSimilarity}");

        $hasLowSimilarity = !$topMatch || !isset($topMatch->similarity) || $topSimilarity < 0.65;

        if ($hasLowSimilarity &&$this->isLoggableInquiry($userMessage)) {$this->logUnhandledQuery($userMessage,$request);
        }

        $systemPrompt = $this->buildSystemPrompt($relevantArticles);
        $contents     =$this->formatHistoryContents($history,$userMessage);

        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => $systemPrompt]]
            ],
            'contents'         => $contents,
            'generationConfig' => [
                'maxOutputTokens' => 2000,
                'temperature'     => 0.3,
            ]
        ];

        return [
            'userMessage'  => $userMessage,
            'history'      => $history,
            'systemPrompt' => $systemPrompt,
            'payload'      => $payload,
        ];
    }

    /**
     * Formats conversation history and user message into Gemini's payload structure.
     */
    private function formatHistoryContents(array $history, string$userMessage): array
    {
        $contents = [];

        foreach ($history as $turn) {$contents[] = [
                'role'  => $turn['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $turn['content']]],
            ];
        }

        $contents[] = [
            'role'  => 'user',
            'parts' => [['text' => $userMessage]],
        ];

        return $contents;
    }

    private function retrieveRelevantKnowledge(string $userMessage, int $limit = 3)     {$queryEmbedding = $this->getEmbedding($userMessage);

        if (!$queryEmbedding) {
            return ChatbotKnowledge::query()->limit($limit)->get(['question', 'answer', 'category']);
        }

        $articles = ChatbotKnowledge::whereNotNull('embedding')->get(['question', 'answer', 'category', 'embedding']);

        if ($articles->isEmpty()) {
            return ChatbotKnowledge::query()->limit($limit)->get(['question', 'answer', 'category']);
        }

        $scoredArticles =$articles->map(function ($article) use ($queryEmbedding) {
            $storedEmbedding   = is_string($article->embedding) ? json_decode($article->embedding, true) :$article->embedding;
            $article->similarity =$this->cosineSimilarity($queryEmbedding,$storedEmbedding ?? []);
            return $article;
        });

        return $scoredArticles->sortByDesc('similarity')->take($limit);
    }

    private function getEmbedding(string $text): ?array
    {
        $apiKey = env('AI_API_KEY') ?? config('services.ai.key');
        $model  = 'gemini-embedding-001';

        try {
            $http = Http::withHeaders(['x-goog-api-key' =>$apiKey])
                ->timeout(15)
                ->connectTimeout(10);

            if (app()->environment('local')) {
                $http->withoutVerifying();
            }

            $response = $http->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:embedContent", [
                'model'   => "models/{$model}",
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

    private function cosineSimilarity(array $vecA, array$vecB): float
    {
        if (empty($vecA) | empty($vecB) || count($vecA) !== count($vecB)) {
            return 0.0;
        }

        $dotProduct = 0;
        $normA      = 0;
        $normB      = 0;

        for ($i = 0; $i < count($vecA);$i++) {
            $dotProduct +=$vecA[$i] *$vecB[$i];$normA      += $vecA[$i] ** 2;
            $normB      += $vecB[$i] ** 2;
        }

        $denominator = sqrt($normA) * sqrt($normB);
        return $denominator == 0 ? 0.0 : $dotProduct / $denominator;
    }

    private function buildSystemPrompt($articles): string
    {
        $context =$articles->isEmpty()
            ? 'No matching articles were found in the knowledge base.'
            : $articles->map(fn ($a) => "Q: {$a->question}\nA: {$a->answer}")->implode("\n\n");

        return <<<PROMPT
You are Leon, the friendly lion mascot and official AI assistant for StudiOUS (Pangasinan State University Open University Systems student services portal).

Provide clear and accurate answers grounded ONLY in the knowledge base context provided below. Always structure your responses neatly using distinct paragraphs, bullet points, or step-by-step lists with proper numbering when providing detailed instructions or multi-part answers. If relevant, provide links to appropriate sections of the StudiOUS portal or official resources for further guidance.

Strict Constraints:

* RESPONSE STRUCTURE & FORMATTING:
  - Headers: Use bold section headings on their own line for major groups (e.g., **Requirements:** or **Application Steps:**).
  - Unordered Lists (Requirements/Features): Always start bullet points with a dash (`- `) on a new line (e.g., `- Completion of academic requirements`).
  - Ordered Lists (Sequential Steps): Always start sequential steps with explicit numbering (`1. `, `2. `, `3. `) on a new line (e.g., `1. **Submit Form:** Fill out the form...`).
  - Sub-bullets: Indent sub-details under numbered steps using two spaces and a dash (`  - `).
  - Spacing: Leave an empty line before and after lists to ensure proper Markdown rendering.

* ANSWER PRECISION & CONTEXT DUMPING:
  - If the user asks a specific sub-question (e.g., "how much is the OTR?"), answer ONLY that question.
  - If the user accepts a follow-up offer (e.g., "yes", "sure") or asks a general question covering multiple aspects, provide ALL relevant details found in the context (requirements, processing time, and steps) in clean, structured sections.
  - Answer ONLY what the user explicitly asks for in your direct response.
  - DO NOT dump full context entries (such as listing all steps, timelines, and requirements) if the user only asked a specific sub-question (e.g., fee, deadline, or location).
  - Give a direct, concise answer first.

* FOLLOW-UP SUGGESTION CHIPS:
  - Keep your main response text concise and direct.
  - If additional relevant requirements, steps, or related details exist in the retrieved context, DO NOT ask conversational follow-up questions at the end of your message.
  - Instead, append dynamic follow-up chips at the very end of your response on a new line using this exact format:
    CHIPS: [Label 1] | [Label 2] | [Label 3]
  - Guidelines for chips:
    * Include 2 to 4 concise, max of 5, contextually relevant options based on the available knowledge base details, put the most relevant first.
    * Each label must be a clear, clickable action phrase, make sure to start them in to How, When, What, Where, or Why (e.g., [How to...], [What are...], [When...]).
    * DO NOT output words like "and so on", "etc.", or generic text inside or outside the brackets.
    * Check conversation history: NEVER repeat topics or questions that the user has already asked about or selected previously.

* LINK FORMATTING:
  - Whenever you mention LandBank's Link.BizPortal, ALWAYS format it strictly as a Markdown hyperlink: [LandBank Link.BizPortal](https://www.lbp-eservices.com/egps/portal/index.jsp).
  - Always format external links and portal URLs using standard Markdown links with full HTTPS protocols.
  - Never display long, raw URLs directly in plain text without markdown anchor tags.

* GREETINGS:
  - If the user sends a casual greeting (e.g., "hi", "hello", "hey", "good morning") without asking a specific question, ignore the knowledge base context and respond warmly as Leon, briefly welcoming them and asking how you can help with their StudiOUS concerns today.
  - Do not include greetings or re-introductions in standard Q&A replies, as the user has already been greeted when opening the assistant. Go straight to answering the query.

* GROUND TRUTH & MISSING INFO:
  - Answer ONLY using the knowledge base context provided below. Never guess, extrapolate, or invent fees, dates, or university policies.
  - Avoid repeating the same information multiple times in a single response.
  - If the provided context does not contain enough information to answer fully, state clearly that you do not have that specific information yet and kindly direct the student to check the Knowledge Base page or submit a Helpdesk ticket.

* PRIVACY & ACCOUNT ACCESS:
  - Never ask for, request, or reference a specific student's personal application status, payment details, or private account information—you have no access to live user records.
  - Always redirect account-specific, personal status, or private payment inquiries directly to the student services portal Application Tracker or the Helpdesk.

KNOWLEDGE BASE CONTEXT:
{$context}
PROMPT;
    }

    private function callAiApi(string $systemPrompt, array $history, string$userMessage): string
    {
        $contents =$this->formatHistoryContents($history,$userMessage);

        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => $systemPrompt]]
            ],
            'contents'         => $contents,
            'generationConfig' => [
                'maxOutputTokens' => 2000,
                'temperature'     => 0.3,
            ]
        ];

        $apiKey       = env('AI_API_KEY') ?? config('services.ai.key');
        $primaryModel  = config('services.ai.model', 'gemini-3.6-flash');$fallbackModel = 'gemini-3.5-flash';

        $response = $this->sendGeminiPost($primaryModel, $apiKey,$payload);

        if ($response->status() === 429) {
            Log::warning("Gemini primary model ({$primaryModel}) hit rate limit (429). Retrying with fallback ({$fallbackModel}).");
            $response = $this->sendGeminiPost($fallbackModel, $apiKey,$payload);
        }

        if ($response->failed()) {
            throw new \RuntimeException('Gemini API returned status ' . $response->status() . ': ' .$response->body());
        }

        return $response->json('candidates.0.content.parts.0.text')
            ?? "I'm not sure how to answer that yet — please try rephrasing, or go to Knowledge-based page or open a Helpdesk ticket.";
    }

    private function sendGeminiPost(string $model, string $apiKey, array$payload)
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        $http = Http::withHeaders([
            'Content-Type'   => 'application/json',
            'x-goog-api-key' => $apiKey
        ])->timeout(65);

        if (app()->environment('local')) {
            $http->withoutVerifying();
        }

        return $http->post($url,$payload);
    }
    
    private function logUnhandledQuery(string $message, Request$request): void
    {
        try {
            UnhandledChatbotQuery::forceCreate([
                'user_message' => $message,
                'ip_address'   => $request->ip(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed logging unhandled query to DB: ' . $e->getMessage());
        }
    }
    
    private function isLoggableInquiry(string $message): bool
    {
        $clean           = strtolower(trim($message));
        $cleanNormalized = preg_replace('/[^\p{L}\p{N}\s]/u', '',$clean);
        $words           = array_values(array_filter(explode(' ',$cleanNormalized)));

        if (count($words) < 2) {
            return false;
        }

        $pureGreetings = [
            'hi', 'hello', 'hey', 'greetings', 'good morning', 
            'good afternoon', 'good evening', 'how are you', 
            'how are you doing', 'what is up', 'sup', 'how is it going'
        ];

        if (count($words) <= 5) {
            foreach ($pureGreetings as$greeting) {
                if (str_contains($clean, $greeting) && count($words) <= 3) {
                    return false;
                }
            }
        }

        return true;
    }
}