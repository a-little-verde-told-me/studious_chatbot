<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use App\Models\ChatbotKnowledge;

Artisan::command('chatbot:test {query}', function ($query) {
    $this->info("Searching for: \"{$query}\"");

    $apiKey = env('AI_API_KEY');

    if (empty($apiKey)) {
        $this->error('Error: AI_API_KEY is empty or missing in your .env file.');
        return;
    }

    $response = Http::timeout(30)
        ->withHeaders(['x-goog-api-key' => $apiKey])
        ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-001:embedContent", [
            'model' => 'models/gemini-embedding-001',
            'content' => ['parts' => [['text' => $query]]]
        ]);

    if ($response->failed()) {
        $this->error('Failed to generate embedding.');
        $this->line($response->body());
        return;
    }

    $queryEmbedding = $response->json('embedding.values');
    $rows = ChatbotKnowledge::all();
    $scored = [];

    foreach ($rows as $row) {
        $rowEmb = is_string($row->embedding) ? json_decode($row->embedding, true) : $row->embedding;
        if (!$rowEmb) continue;

        $dot = 0; $normA = 0; $normB = 0;
        foreach ($queryEmbedding as $i => $val) {
            $other = $rowEmb[$i] ?? 0;
            $dot += $val * $other;
            $normA += $val * $val;
            $normB += $other * $other;
        }
        $similarity = ($normA == 0 || $normB == 0) ? 0 : $dot / (sqrt($normA) * sqrt($normB));

        // Dynamically find the text/content field regardless of column name
        $snippet = 'N/A';
        foreach ($row->getAttributes() as $key => $val) {
            if (!in_array($key, ['id', 'embedding', 'created_at', 'updated_at']) && is_string($val)) {
                $snippet = $val;
                break;
            }
        }

        $scored[] = [
            'id' => $row->id,
            'content' => $snippet,
            'similarity' => $similarity
        ];
    }

    usort($scored, fn($a, $b) => $b['similarity'] <=> $a['similarity']);

    $this->table(
        ['ID', 'Score', 'Content'],
        collect($scored)->take(3)->map(fn($r) => [$r['id'], round($r['similarity'], 4), substr($r['content'], 0, 50)])
    );
});