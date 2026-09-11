<?php

namespace App\Http\Controllers;

use App\Models\ChatbotKnowledge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    public function respond(Request $request)
    {
        $validated = $request->validate([
            'message'           => 'required|string|max:1000',
            'history'           => 'array|max:20',
            'history.*.role'    => 'in:user,assistant',
            'history.*.content' => 'string|max:2000',
        ]);

        $userMessage = trim($validated['message']);
        $history     = $validated['history'] ?? [];

        $relevantArticles = $this->retrieveRelevantKnowledge($userMessage);
        $systemPrompt     = $this->buildSystemPrompt($relevantArticles);

        try {
            $reply = $this->callAiApi($systemPrompt, $history, $userMessage);
        } catch (\Throwable $e) {
            Log::warning('Leon chatbot API call failed: ' . $e->getMessage());

            return response()->json([
                'reply'    => "I'm having trouble reaching my brain right now. "
                            . "Please try again in a moment, or open a Helpdesk "
                            . "ticket and our team will help directly.",
                'fallback' => true,
            ]);
        }

        return response()->json(['reply' => $reply]);
    }

    private function retrieveRelevantKnowledge(string $question, int $limit = 4)
    {
        $keywords = collect(preg_split('/\s+/', strtolower($question)))
            ->filter(fn ($w) => strlen($w) >= 3)
            ->take(6);

        $query = ChatbotKnowledge::query();

        if ($keywords->isNotEmpty()) {
            $query->where(function ($q) use ($keywords) {
                foreach ($keywords as $word) {
                    $q->orWhere('question', 'like', "%{$word}%")
                    ->orWhere('answer', 'like', "%{$word}%")
                    ->orWhere('category', 'like', "%{$word}%");
                }
            });
        }

        $results = $query->limit($limit)->get(['question', 'answer', 'category']);

        // If no specific keyword match is found, fetch top default articles
        if ($results->isEmpty()) {
            return ChatbotKnowledge::query()->limit($limit)->get(['question', 'answer', 'category']);
        }

        return $results;
    }

    private function buildSystemPrompt($articles): string
    {
        $context = $articles->isEmpty()
            ? 'No matching articles were found in the knowledge base.'
            : $articles->map(fn ($a) => "Q: {$a->question}\nA: {$a->answer}")->implode("\n\n");

        return <<<PROMPT
You are Leon, the friendly lion mascot and official AI assistant for PSU-StudiOUS (Pangasinan State University Open University Systems portal).

Provide clear, thorough, and well-detailed answers grounded ONLY in the knowledge base context provided below. Whenever applicable, structure your responses using distinct paragraphs, bullet points, or step-by-step lists to make instructions easy to follow.

Strict Constraints:
- Answer ONLY using the knowledge base context provided below. Never guess, extrapolate, or invent fees, dates, or university policies.
- If the provided context does not contain enough information to answer fully, state clearly that you do not have that specific information yet and kindly direct the student to submit a Helpdesk ticket.
- Never ask for, request, or reference a specific student's personal application status, payment details, or private account information—you have no access to live user records.
- Always redirect account-specific, personal status, or private payment inquiries directly to the student portal Application Tracker or the Helpdesk.

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

        // Construct full URL with API key parameter
        $baseUrl = config('services.ai.url');
        $apiKey  = config('services.ai.key');
        $endpoint = str_contains($baseUrl, '?') ? "{$baseUrl}&key={$apiKey}" : "{$baseUrl}?key={$apiKey}";

        $response = Http::withoutVerifying()
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->timeout(30)
            ->post($endpoint, [
                'systemInstruction' => [
                    'parts' => [['text' => $systemPrompt]]
                ],
                'contents' => $contents,
                'generationConfig' => [
                    'maxOutputTokens' => 800,
                    'temperature'     => 0.3,
                ]
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Gemini API returned status ' . $response->status() . ': ' . $response->body());
        }

        return $response->json('candidates.0.content.parts.0.text')
            ?? "I'm not sure how to answer that yet — please try rephrasing, or open a Helpdesk ticket.";
    }
}