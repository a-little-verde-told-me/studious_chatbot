<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chatbot_knowledge', function (Blueprint $table) {
            // Individual indexes for fast string lookups on search targets
            $table->index('question');
            $table->index('answer');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::table('chatbot_knowledge', function (Blueprint $table) {
            $table->dropIndex(['question']);
            $table->dropIndex(['answer']);
            $table->dropIndex(['category']);
        });
    }
};