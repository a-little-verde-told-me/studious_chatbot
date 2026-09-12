<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\ChatbotKnowledge;
use App\Observers\ChatbotKnowledgeObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

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

    if ($this->app->environment('production')) {
        URL::forceScheme('https');
    }

    RateLimiter::for('chatbot', function (Request $request) {
        return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
    });
    }
}
