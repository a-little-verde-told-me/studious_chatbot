<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\ChatbotKnowledge;
use App\Observers\ChatbotKnowledgeObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
    ChatbotKnowledge::observe(ChatbotKnowledgeObserver::class);
    }
}
