<?php

namespace Database\Seeders;

use App\Models\AiModel;
use App\Models\Prompt;
use Illuminate\Database\Seeder;

class DefaultsSeeder extends Seeder
{
    public function run(): void
    {
        AiModel::firstOrCreate(
            ['provider_id' => 'google/gemini-2.0-flash-exp:free'],
            [
                'name' => 'Gemini 2.0 Flash (Image)',
                'type' => 'image',
                'is_default' => true,
                'is_active' => true,
            ]
        );

        AiModel::firstOrCreate(
            ['provider_id' => 'google/gemini-2.0-flash-exp:free'],
            [
                'name' => 'Gemini 2.0 Flash (Text)',
                'type' => 'text',
                'is_default' => true,
                'is_active' => true,
            ]
        );

        Prompt::firstOrCreate(
            ['name' => 'Default Recipe Prompt', 'type' => 'recipe'],
            [
                'content' => 'Generate a beautiful, appetizing visual representation of the dish "{{recipe_name}}". Create multiple images showing the dish from different angles, including close-up shots of key ingredients and the final plated presentation. The images should be professional food photography quality with proper lighting and styling.',
                'is_default' => true,
                'is_active' => true,
            ]
        );

        Prompt::firstOrCreate(
            ['name' => 'Default Title Prompt', 'type' => 'title'],
            [
                'content' => 'Generate a creative, appetizing title for the dish "{{recipe_name}}". The title should be catchy and make people want to try the recipe. Return only the title, nothing else.',
                'is_default' => true,
                'is_active' => true,
            ]
        );

        Prompt::firstOrCreate(
            ['name' => 'Default Ingredients Prompt', 'type' => 'ingredients'],
            [
                'content' => 'List all the ingredients needed to make "{{recipe_name}}". Format as a simple list with quantities. Be thorough and include all necessary ingredients including seasonings and garnishes.',
                'is_default' => true,
                'is_active' => true,
            ]
        );
    }
}
