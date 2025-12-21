<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prompt_experiments', function (Blueprint $table) {
            $table->text('prompt_content')->nullable()->after('recipe_name');
            $table->foreignId('prompt_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('prompt_experiments', function (Blueprint $table) {
            $table->dropColumn('prompt_content');
        });
    }
};
