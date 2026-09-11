<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ChatbotKnowledge;
use Illuminate\Support\Facades\Http;

class GenerateKnowledgeEmbeddings extends Command
{
    protected $signature = 'chatbot:generate-embeddings';
    protected $description = 'Generate vector embeddings for all chatbot knowledge base records';

    public function handle()
    {
        $this->info('Generating embeddings for knowledge base...');

        $articles = ChatbotKnowledge::all();
        $apiKey = config('services.ai.key');
        $model = 'gemini-embedding-001';

        foreach ($articles as $article) {
            $textToEmbed = "Q: {$article->question}\nA: {$article->answer}";

            $response = Http::withoutVerifying()
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:embedContent?key={$apiKey}", [
                    'model' => "models/{$model}",
                    'content' => [
                        'parts' => [['text' => $textToEmbed]]
                    ]
                ]);

            if ($response->successful()) {
                $embedding = $response->json('embedding.values');
                $article->embedding = json_encode($embedding);
                $article->save();

                $this->line("Updated embedding for: ID {$article->id}");
            } else {
                $this->error("Failed for ID {$article->id}: " . $response->body());
            }

            // Sleep briefly to avoid hitting rate limits on free tier loops
            usleep(500000); 
        }

        $this->info('All embeddings generated successfully!');
    }
}