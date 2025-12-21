<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $generation->title ?? $generation->recipe_name }} - Visual Recipe</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="min-h-screen py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-sm text-gray-400 font-mono">#{{ $generation->id }}</span>
                        <h1 class="text-2xl font-bold">{{ $generation->title ?? $generation->recipe_name }}</h1>
                    </div>
                    <p class="text-gray-500 text-sm mb-6">Shared recipe visualization</p>

                    @if($generation->images && count($generation->images) > 0)
                    <div class="space-y-4 mb-6">
                        @foreach($generation->images as $image)
                        <img src="{{ Storage::url($image) }}" alt="Recipe image" class="rounded-lg shadow-md w-full h-auto">
                        @endforeach
                    </div>
                    @endif

                    @if($generation->ingredients)
                    <div class="mb-6">
                        <h2 class="text-lg font-medium mb-2">Ingredients</h2>
                        <div class="bg-gray-50 p-4 rounded-lg whitespace-pre-wrap text-gray-700">{{ $generation->ingredients }}</div>
                    </div>
                    @endif

                    <div class="text-center text-gray-400 text-sm mt-8">
                        Created with Visual Recipe Generator
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
