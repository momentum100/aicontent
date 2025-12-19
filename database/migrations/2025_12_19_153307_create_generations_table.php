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
        Schema::create('generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('recipe_name');
            $table->foreignId('model_id')->constrained('ai_models')->onDelete('cascade');
            $table->foreignId('prompt_id')->constrained('prompts')->onDelete('cascade');
            $table->foreignId('title_prompt_id')->nullable()->constrained('prompts')->onDelete('set null');
            $table->foreignId('ingredients_prompt_id')->nullable()->constrained('prompts')->onDelete('set null');
            $table->string('title')->nullable();
            $table->text('ingredients')->nullable();
            $table->json('images')->nullable();
            $table->integer('tokens_used')->nullable();
            $table->decimal('cost', 10, 6)->nullable();
            $table->json('raw_response')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->string('share_token')->unique()->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generations');
    }
};
