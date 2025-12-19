<?php

namespace App\Contracts;

use App\Models\AiModel;
use App\Models\Prompt;

interface TextGeneratorInterface
{
    public function generate(string $recipeName, AiModel $model, Prompt $prompt): array;
}
