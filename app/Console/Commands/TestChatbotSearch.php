<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\ChatbotKnowledge; // Update to match your actual Model name

class TestChatbotSearch extends Command
{
    protected $signature = 'chatbot:test {query}';
    protected $description = 'Test semantic search retrieval against the knowledge base';

    public function handle()
    {
        $queryString = $this->argument('query');
        $this->info("Searching for: \"{$queryString}\"\n");

        // 1. Generate an embedding for the user's test query using Gemini API
        $apiKey = config('services.gemini.key'); // Or env('GEMINI_API_KEY')
        $response = Http::timeout(30)->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-001:embedContent?key={$apiKey}", [
            'model' => 'models/gemini-embedding-001',
            'content' => [
                'parts' => [['text' => $queryString]]
            ]
        ]);

        if ($response->failed()) {
            $this->error('Failed to generate embedding for the query.');
            return;
        }

        $queryEmbedding = $response->json('embedding.values');

        // 2. Fetch all knowledge rows from the database
        $knowledgeRows = ChatbotKnowledge::all();
        $scoredRows = [];

        foreach ($knowledgeRows as $row) {
            // Assuming your database casts the embedding column to an array, 
            // or you need to json_decode it:
            $rowEmbedding = is_string($row->embedding) ? json_decode($row->embedding, true) : $row->embedding;

            if (!$rowEmbedding) {
                continue;
            }

            // 3. Calculate Cosine Similarity between query and row vector
            $similarity = $this->cosineSimilarity($queryEmbedding, $rowEmbedding);

            $scoredRows[] = [
                'id' => $row->id,
                'content' => $row->content ?? $row->text ?? 'N/A', // Adjust column name as needed
                'similarity' => $similarity
            ];
        }

        // 4. Sort results by highest similarity score
        usort($scoredRows, fn($a, $b) => $b['similarity'] <=> $a['similarity']);

        // 5. Display top 3 matches in a table
        $this->table(
            ['ID', 'Similarity Score', 'Content Snippet'],
            collect($scoredRows)->take(3)->map(fn($r) => [
                $r['id'], 
                round($r['similarity'], 4), 
                substr($r['content'], 0, 60) . '...'
            ])
        );
    }

    private function cosineSimilarity(array $a, array $b): float
    {
        $dot = 0;
        $normA = 0;
        $normB = 0;

        foreach ($a as $i => $val) {
            $other = $b[$i] ?? 0;
            قات = $dot + ($val * $other); // handled below correctly via standard summation
            $dot += $val * $other;
            $normA += $val * $val;
            $normB += $other * $other;
        }

        if ($normA == 0 || $normB == 0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}