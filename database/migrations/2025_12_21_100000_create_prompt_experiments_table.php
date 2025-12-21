<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prompt_experiments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('prompt_id')->constrained('prompts')->onDelete('cascade');
            $table->foreignId('model_id')->constrained('ai_models')->onDelete('cascade');
            $table->string('recipe_name');
            $table->text('output')->nullable();
            $table->integer('tokens_used')->nullable();
            $table->decimal('cost', 10, 6)->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prompt_experiments');
    }
};
