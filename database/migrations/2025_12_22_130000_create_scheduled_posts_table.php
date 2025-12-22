<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('generation_id')->constrained()->onDelete('cascade');
            $table->string('postiz_post_id')->nullable();
            $table->string('channel');
            $table->string('integration_id');
            $table->dateTime('scheduled_at');
            $table->enum('status', ['pending', 'scheduled', 'published', 'failed'])->default('pending');
            $table->text('content')->nullable();
            $table->json('images')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_posts');
    }
};
