<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chatbot_knowledge', function (Blueprint $table) {
            $table->json('embedding')->nullable()->after('answer');
        });
    }

    public function down(): void
    {
        Schema::table('chatbot_knowledge', function (Blueprint $table) {
            $table->dropColumn('embedding');
        });
    }
};