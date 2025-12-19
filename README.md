# Visual Recipe Generator

A Laravel application for generating AI-powered visual recipe guides using OpenRouter API with Gemini models.

## Architecture

### Overview

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│   Frontend      │────▶│   API Controller │────▶│   Queue Job     │
│   (Alpine.js)   │     │                  │     │                 │
└─────────────────┘     └──────────────────┘     └────────┬────────┘
        │                                                  │
        │                                                  ▼
        │                                        ┌─────────────────┐
        │◀───────────── Poll Status ─────────────│  AI Services    │
        │                                        │  (OpenRouter)   │
        └────────────────────────────────────────┴─────────────────┘
```

### Key Components

- **Frontend**: Alpine.js single-page app with tab navigation
- **API Controllers**: Laravel REST controllers for all operations
- **Queue Jobs**: Background processing for long-running AI generation
- **Services**:
  - `AiImageService` - Image generation via OpenRouter (Gemini)
  - `AiTextService` - Text generation for titles/ingredients

### Generation Flow

1. User submits recipe name + selects prompts/models
2. Controller creates `Generation` record with `status: processing`
3. `GenerateRecipeJob` is dispatched to queue
4. Frontend polls `/api/generations/{id}/status` every 2 seconds
5. Job completes, updates record with images/text
6. Frontend detects completion, loads full result

## Requirements

- PHP 8.3+
- MySQL 8.0+
- Node.js 18+
- Redis or database queue driver

## Installation

```bash
# Clone and install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Set up database
php artisan migrate
php artisan db:seed

# Build assets
npm run build

# Create storage link
php artisan storage:link
```

## Configuration

Add to `.env`:

```env
OPENROUTER_API_KEY=your_api_key
OPENROUTER_BASE_URL=https://openrouter.ai/api/v1

# Queue configuration (required for background jobs)
QUEUE_CONNECTION=database
```

## Running

```bash
# Start web server
php artisan serve

# Start queue worker (required for generation)
php artisan queue:work --timeout=600

# Or for development with auto-restart
php artisan queue:listen --timeout=600
```

## API Endpoints

### Generations
- `POST /api/generate` - Start new generation (async)
- `GET /api/generations` - List user's generations
- `GET /api/generations/{id}` - Get generation details
- `GET /api/generations/{id}/status` - Poll generation status
- `POST /api/generations/{id}/share` - Toggle sharing
- `DELETE /api/generations/{id}` - Delete generation

### Queue Stats
- `GET /api/queue/stats` - Get queue status (pending/processing counts)

### Models & Prompts
- `GET/POST/PUT /api/models` - CRUD for AI models
- `GET/POST/PUT /api/prompts` - CRUD for prompts
- `GET /api/defaults` - Get default selections

## Features

- **Image Generation**: 9:16 vertical images for recipe steps
- **Text Generation**: Titles, ingredients lists
- **Instructions**: Full recipe instructions from AI
- **Queue System**: Background processing with status polling
- **Sharing**: Public share links for generations
- **Multi-user**: Admin and operator roles
- **Action Logs**: Full audit trail

## File Structure

```
app/
├── Http/Controllers/Api/
│   ├── GenerationController.php  # Main generation logic
│   ├── ModelController.php       # AI model management
│   └── PromptController.php      # Prompt management
├── Jobs/
│   └── GenerateRecipeJob.php     # Background generation
├── Models/
│   ├── Generation.php
│   ├── AiModel.php
│   └── Prompt.php
└── Services/
    ├── AiImageService.php        # OpenRouter image API
    └── AiTextService.php         # OpenRouter text API

resources/views/
└── app.blade.php                 # Main SPA view

storage/app/
├── public/generations/           # Generated images
└── private/logs/generations/     # Raw API response logs
```

## Queue Job Timeouts

The `GenerateRecipeJob` has a 10-minute timeout (`$timeout = 600`) to accommodate slow image generation. Make sure your queue worker matches:

```bash
php artisan queue:work --timeout=600
```
