<x-app-layout>
    <div class="py-6" x-data="app()" x-init="init()">
        <!-- Toast Notification -->
        <div x-show="toast" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-2"
            class="fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg"
            :class="toast?.type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'">
            <span x-text="toast?.message"></span>
        </div>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Navigation Tabs -->
            <div class="mb-6 border-b border-gray-200">
                <nav class="-mb-px flex space-x-8 items-center">
                    <button @click="currentTab = 'generate'"
                        :class="currentTab === 'generate' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        Generate
                    </button>
                    <button @click="currentTab = 'history'"
                        :class="currentTab === 'history' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        History
                    </button>
                    <button @click="currentTab = 'models'"
                        :class="currentTab === 'models' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        Models
                    </button>
                    <button @click="currentTab = 'prompts'"
                        :class="currentTab === 'prompts' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        Prompts
                    </button>
                    <button @click="currentTab = 'playground'"
                        :class="currentTab === 'playground' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        Playground
                    </button>
                    @if(auth()->user()->isAdmin())
                    <button @click="currentTab = 'logs'"
                        :class="currentTab === 'logs' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        Action Logs
                    </button>
                    <button @click="currentTab = 'users'"
                        :class="currentTab === 'users' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        Users
                    </button>
                    @endif
                    <!-- Queue Stats -->
                    <div class="ml-auto flex items-center gap-2 text-xs">
                        <span class="px-2 py-1 rounded" :class="queueStats.processing > 0 ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-500'">
                            <span x-text="queueStats.processing"></span> processing
                        </span>
                        <span class="px-2 py-1 rounded" :class="queueStats.pending > 0 ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500'">
                            <span x-text="queueStats.pending"></span> queued
                        </span>
                    </div>
                </nav>
            </div>

            <!-- Generate Tab -->
            <div x-show="currentTab === 'generate'" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form @submit.prevent="generate()">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Recipe Name</label>
                            <input type="text" x-model="form.recipe_name" required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Enter recipe name...">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Image Model</label>
                                <select x-model="form.model_id" required
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <template x-for="model in defaults.models?.image" :key="model.id">
                                        <option :value="model.id" x-text="model.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Recipe Prompt</label>
                                <select x-model="form.prompt_id" required
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <template x-for="prompt in defaults.prompts?.recipe" :key="prompt.id">
                                        <option :value="prompt.id" x-text="prompt.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Title Prompt (Optional)</label>
                                <select x-model="form.title_prompt_id"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">None</option>
                                    <template x-for="prompt in defaults.prompts?.title" :key="prompt.id">
                                        <option :value="prompt.id" x-text="prompt.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Ingredients Prompt (Optional)</label>
                                <select x-model="form.ingredients_prompt_id"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">None</option>
                                    <template x-for="prompt in defaults.prompts?.ingredients" :key="prompt.id">
                                        <option :value="prompt.id" x-text="prompt.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <button type="submit"
                            class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700">
                            Generate Recipe
                        </button>
                    </form>

                    <!-- Result -->
                    <div x-show="result" class="mt-8 border-t pt-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium">Result</h3>
                            <div class="flex items-center gap-2">
                                <span x-show="result?.model" class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded" x-text="'Img: ' + result?.model?.name"></span>
                                <span x-show="result?.text_model" class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded" x-text="'Txt: ' + result?.text_model?.name"></span>
                                <span class="text-sm text-gray-500" x-text="'Cost: $' + parseFloat(result?.cost || 0).toFixed(6)"></span>
                            </div>
                        </div>

                        <div x-show="result?.title" class="mb-4">
                            <div class="flex items-center gap-2">
                                <h4 class="font-medium" x-text="result?.title"></h4>
                                <button @click="copyToClipboard(result?.title)" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div x-show="result?.ingredients" class="mb-4">
                            <div class="flex items-center gap-2 mb-2">
                                <h4 class="font-medium">Ingredients</h4>
                                <button @click="copyToClipboard(result?.ingredients)" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                </button>
                            </div>
                            <p class="text-gray-600 whitespace-pre-wrap" x-text="result?.ingredients"></p>
                        </div>

                        <div x-show="result?.instructions" class="mb-4">
                            <div class="flex items-center gap-2 mb-2">
                                <h4 class="font-medium">Instructions</h4>
                                <button @click="copyToClipboard(result?.instructions)" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="text-gray-600 whitespace-pre-wrap prose prose-sm max-w-none" x-text="result?.instructions"></div>
                        </div>

                        <div x-show="result && result.images && result.images.length > 0" class="grid grid-cols-3 md:grid-cols-5 lg:grid-cols-7 gap-3">
                            <template x-for="(image, index) in (result?.images || [])" :key="index">
                                <img :src="'/storage/' + image" class="rounded-lg shadow-md w-full h-auto" :alt="'Image ' + (index + 1)">
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- History Tab -->
            <div x-show="currentTab === 'history'" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium mb-4">Generation History</h3>
                    <div class="space-y-2">
                        <template x-for="gen in history" :key="gen.id">
                            <div class="border rounded px-3 py-2 flex items-center justify-between gap-4">
                                <div class="flex items-center gap-4 min-w-0 flex-wrap">
                                    <span class="text-xs text-gray-400 font-mono" x-text="'#' + gen.id"></span>
                                    <span class="font-medium truncate" x-text="gen.recipe_name"></span>
                                    <span class="text-xs text-gray-400 whitespace-nowrap" x-text="new Date(gen.created_at).toLocaleDateString()"></span>
                                    <span class="text-xs text-gray-400 whitespace-nowrap" x-text="'$' + parseFloat(gen.cost || 0).toFixed(4)"></span>
                                    <span x-show="gen.model" class="text-xs bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded whitespace-nowrap" x-text="'Img: ' + gen.model?.name"></span>
                                    <span x-show="gen.text_model" class="text-xs bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded whitespace-nowrap" x-text="'Txt: ' + gen.text_model?.name"></span>
                                </div>
                                <div class="flex gap-1 flex-shrink-0">
                                    <button @click="toggleShare(gen)" class="text-xs px-2 py-1 rounded"
                                        :class="gen.is_public ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
                                        x-text="gen.is_public ? 'Shared' : 'Share'"></button>
                                    <button @click="viewGeneration(gen)" class="text-xs px-2 py-1 bg-indigo-100 text-indigo-700 rounded">View</button>
                                    <button @click="deleteGeneration(gen.id)" class="text-xs px-2 py-1 bg-red-100 text-red-700 rounded">Del</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Models Tab -->
            <div x-show="currentTab === 'models'" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium">AI Models</h3>
                        <button @click="editingModel = null; modelForm = { name: '', provider_id: '', type: 'image', is_default: false, is_active: true }; showModelForm = !showModelForm" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm">
                            Add Model
                        </button>
                    </div>

                    <div x-show="showModelForm" class="mb-6 p-4 border rounded-lg bg-gray-50">
                        <h4 class="font-medium mb-3" x-text="editingModel ? 'Edit Model' : 'Add Model'"></h4>
                        <form @submit.prevent="saveModel()">
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <input type="text" x-model="modelForm.name" placeholder="Model Name" required
                                    class="rounded-md border-gray-300">
                                <input type="text" x-model="modelForm.provider_id" placeholder="Provider ID (e.g., google/gemini...)" required
                                    class="rounded-md border-gray-300">
                            </div>
                            <div class="grid grid-cols-3 gap-4 mb-4">
                                <select x-model="modelForm.type" required class="rounded-md border-gray-300">
                                    <option value="image">Image</option>
                                    <option value="text">Text</option>
                                </select>
                                <label class="flex items-center">
                                    <input type="checkbox" x-model="modelForm.is_default" class="rounded mr-2">
                                    Default
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" x-model="modelForm.is_active" class="rounded mr-2">
                                    Active
                                </label>
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm">Save</button>
                                <button type="button" @click="showModelForm = false; editingModel = null" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm">Cancel</button>
                            </div>
                        </form>
                    </div>

                    <div class="space-y-2">
                        <template x-for="model in models" :key="model.id">
                            <div class="flex justify-between items-center p-3 border rounded" :class="!model.is_active ? 'bg-gray-100 opacity-60' : ''">
                                <div>
                                    <span class="font-medium" x-text="model.name"></span>
                                    <span class="text-sm text-gray-500 ml-2" x-text="'(' + model.type + ')'"></span>
                                    <span x-show="model.is_default" class="ml-2 text-xs bg-green-100 text-green-700 px-2 py-1 rounded">Default</span>
                                    <span x-show="!model.is_active" class="ml-2 text-xs bg-red-100 text-red-700 px-2 py-1 rounded">Disabled</span>
                                </div>
                                <div class="flex gap-2">
                                    <button @click="editModel(model)" class="text-sm px-3 py-1 bg-blue-100 text-blue-700 rounded">Edit</button>
                                    <button @click="toggleModelActive(model)"
                                        class="text-sm px-3 py-1 rounded"
                                        :class="model.is_active ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700'"
                                        x-text="model.is_active ? 'Disable' : 'Enable'"></button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Prompts Tab -->
            <div x-show="currentTab === 'prompts'" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium">Prompts</h3>
                        <button @click="editingPrompt = null; promptForm = { name: '', type: 'recipe', content: '', is_default: false, is_active: true }; showPromptForm = !showPromptForm" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm">
                            Add Prompt
                        </button>
                    </div>

                    <div x-show="showPromptForm" class="mb-6 p-4 border rounded-lg bg-gray-50">
                        <h4 class="font-medium mb-3" x-text="editingPrompt ? 'Edit Prompt' : 'Add Prompt'"></h4>
                        <form @submit.prevent="savePrompt()">
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <input type="text" x-model="promptForm.name" placeholder="Prompt Name" required
                                    class="rounded-md border-gray-300">
                                <select x-model="promptForm.type" required class="rounded-md border-gray-300">
                                    <option value="recipe">Recipe</option>
                                    <option value="title">Title</option>
                                    <option value="ingredients">Ingredients</option>
                                </select>
                            </div>
                            <textarea x-model="promptForm.content" placeholder="Prompt content... Use @{{recipe_name}} as placeholder" required
                                class="w-full rounded-md border-gray-300 mb-4" rows="4"></textarea>
                            <div class="flex gap-4 mb-4">
                                <label class="flex items-center">
                                    <input type="checkbox" x-model="promptForm.is_default" class="rounded mr-2">
                                    Default
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" x-model="promptForm.is_active" class="rounded mr-2">
                                    Active
                                </label>
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm">Save</button>
                                <button type="button" @click="showPromptForm = false; editingPrompt = null" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm">Cancel</button>
                            </div>
                        </form>
                    </div>

                    <div class="space-y-2">
                        <template x-for="prompt in prompts" :key="prompt.id">
                            <div class="flex justify-between items-center p-3 border rounded" :class="!prompt.is_active ? 'bg-gray-100 opacity-60' : ''">
                                <div>
                                    <span class="font-medium" x-text="prompt.name"></span>
                                    <span class="text-sm text-gray-500 ml-2" x-text="'(' + prompt.type + ')'"></span>
                                    <span x-show="prompt.is_default" class="ml-2 text-xs bg-green-100 text-green-700 px-2 py-1 rounded">Default</span>
                                    <span x-show="!prompt.is_active" class="ml-2 text-xs bg-red-100 text-red-700 px-2 py-1 rounded">Disabled</span>
                                </div>
                                <div class="flex gap-2">
                                    <button @click="editPrompt(prompt)" class="text-sm px-3 py-1 bg-blue-100 text-blue-700 rounded">Edit</button>
                                    <button @click="togglePromptActive(prompt)"
                                        class="text-sm px-3 py-1 rounded"
                                        :class="prompt.is_active ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700'"
                                        x-text="prompt.is_active ? 'Disable' : 'Enable'"></button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Playground Tab -->
            <div x-show="currentTab === 'playground'" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium mb-4">Prompt Playground</h3>

                    <!-- Experiment Form -->
                    <form @submit.prevent="runExperiment()" class="mb-6 p-4 border rounded-lg bg-gray-50">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Recipe Name</label>
                                <input type="text" x-model="experimentForm.recipe_name" required
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Enter recipe name...">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Text Model</label>
                                <select x-model="experimentForm.model_id" required
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <template x-for="model in defaults.models?.text" :key="model.id">
                                        <option :value="model.id" x-text="model.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Prompt</label>
                                <select x-model="experimentForm.prompt_id" required
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <template x-for="prompt in prompts" :key="prompt.id">
                                        <option :value="prompt.id" x-text="prompt.name + ' (' + prompt.type + ')'"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                        <button type="submit" :disabled="experimentLoading"
                            class="bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!experimentLoading">Run Experiment</span>
                            <span x-show="experimentLoading">Running...</span>
                        </button>
                    </form>

                    <!-- Current Result -->
                    <div x-show="experimentResult" class="mb-6 p-4 border rounded-lg">
                        <div class="flex justify-between items-center mb-2">
                            <h4 class="font-medium">Latest Result</h4>
                            <div class="flex items-center gap-2">
                                <span x-show="experimentResult?.model" class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded" x-text="experimentResult?.model?.name"></span>
                                <span class="text-xs text-gray-500" x-text="'$' + parseFloat(experimentResult?.cost || 0).toFixed(6)"></span>
                                <button @click="copyToClipboard(experimentResult?.output)" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="bg-gray-50 p-3 rounded whitespace-pre-wrap text-sm" x-text="experimentResult?.output"></div>
                    </div>

                    <!-- Experiment History -->
                    <h4 class="font-medium mb-2">Experiment History</h4>
                    <div class="space-y-2">
                        <template x-for="exp in experiments" :key="exp.id">
                            <div class="border rounded p-3">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-xs text-gray-400 font-mono" x-text="'#' + exp.id"></span>
                                        <span class="font-medium" x-text="exp.recipe_name"></span>
                                        <span class="text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded" x-text="exp.prompt?.name"></span>
                                        <span class="text-xs bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded" x-text="exp.model?.name"></span>
                                        <span class="text-xs text-gray-400" x-text="'$' + parseFloat(exp.cost || 0).toFixed(6)"></span>
                                        <span class="text-xs text-gray-400" x-text="new Date(exp.created_at).toLocaleString()"></span>
                                    </div>
                                    <div class="flex gap-1">
                                        <button @click="copyToClipboard(exp.output)" class="text-xs px-2 py-1 bg-gray-100 text-gray-600 rounded">Copy</button>
                                        <button @click="deleteExperiment(exp.id)" class="text-xs px-2 py-1 bg-red-100 text-red-700 rounded">Del</button>
                                    </div>
                                </div>
                                <div class="bg-gray-50 p-2 rounded text-sm whitespace-pre-wrap max-h-32 overflow-y-auto" x-text="exp.output"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Action Logs Tab (Admin Only) -->
            @if(auth()->user()->isAdmin())
            <div x-show="currentTab === 'logs'" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium mb-4">Action Logs</h3>
                    <div class="space-y-2">
                        <template x-for="log in logs" :key="log.id">
                            <div class="p-3 border rounded text-sm">
                                <div class="flex justify-between">
                                    <span class="font-medium" x-text="log.user?.name || 'System'"></span>
                                    <span class="text-gray-500" x-text="new Date(log.created_at).toLocaleString()"></span>
                                </div>
                                <p class="text-gray-600">
                                    <span x-text="log.action"></span>
                                    <span class="text-gray-400" x-text="log.model_type?.split('\\\\').pop()"></span>
                                </p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Users Tab (Admin Only) -->
            <div x-show="currentTab === 'users'" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium">Users</h3>
                        <button @click="showUserForm = !showUserForm" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm">
                            Add User
                        </button>
                    </div>

                    <!-- Add User Form -->
                    <div x-show="showUserForm" class="mb-6 p-4 border rounded bg-gray-50">
                        <form @submit.prevent="saveUser()">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <input type="text" x-model="userForm.name" placeholder="Name" required
                                    class="w-full rounded-md border-gray-300">
                                <input type="email" x-model="userForm.email" placeholder="Email" required
                                    class="w-full rounded-md border-gray-300">
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <input type="password" x-model="userForm.password" placeholder="Password" required
                                    class="w-full rounded-md border-gray-300">
                                <select x-model="userForm.role" required class="w-full rounded-md border-gray-300">
                                    <option value="operator">Operator</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm">Save User</button>
                        </form>
                    </div>

                    <!-- Users List -->
                    <div class="space-y-2">
                        <template x-for="user in users" :key="user.id">
                            <div class="flex justify-between items-center p-3 border rounded">
                                <div>
                                    <span class="font-medium" x-text="user.name"></span>
                                    <span class="text-sm text-gray-500 ml-2" x-text="user.email"></span>
                                    <span class="ml-2 text-xs px-2 py-1 rounded"
                                        :class="user.role === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'"
                                        x-text="user.role"></span>
                                    <span x-show="!user.is_active" class="ml-2 text-xs bg-red-100 text-red-700 px-2 py-1 rounded">Disabled</span>
                                </div>
                                <div class="flex gap-2">
                                    <button @click="toggleUserActive(user)"
                                        class="text-sm px-3 py-1 rounded"
                                        :class="user.is_active ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700'"
                                        x-text="user.is_active ? 'Disable' : 'Enable'"></button>
                                    <button @click="deleteUser(user.id)" class="text-red-600 text-sm px-3 py-1 bg-red-100 rounded">Delete</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <script>
        function app() {
            return {
                currentTab: window.location.hash.slice(1) || 'generate',
                loading: false,
                defaults: {},
                form: {
                    recipe_name: '',
                    model_id: null,
                    prompt_id: null,
                    title_prompt_id: '',
                    ingredients_prompt_id: ''
                },
                result: null,
                history: [],
                models: [],
                prompts: [],
                logs: [],
                users: [],
                showModelForm: false,
                showPromptForm: false,
                showUserForm: false,
                editingModel: null,
                editingPrompt: null,
                modelForm: { name: '', provider_id: '', type: 'image', is_default: false, is_active: true },
                promptForm: { name: '', type: 'recipe', content: '', is_default: false, is_active: true },
                userForm: { name: '', email: '', password: '', role: 'operator' },
                queueStats: { pending: 0, processing: 0 },
                toast: null,
                experiments: [],
                experimentForm: { recipe_name: '', prompt_id: null, model_id: null },
                experimentResult: null,
                experimentLoading: false,

                async init() {
                    await Promise.all([
                        this.loadDefaults(),
                        this.loadHistory(),
                        this.loadModels(),
                        this.loadPrompts(),
                        this.loadExperiments(),
                        this.loadQueueStats(),
                        @if(auth()->user()->isAdmin())
                        this.loadLogs(),
                        this.loadUsers(),
                        @endif
                    ]);
                    setInterval(() => this.loadQueueStats(), 3000);

                    // Hash router
                    this.$watch('currentTab', (tab) => {
                        window.location.hash = tab;
                    });
                    window.addEventListener('hashchange', () => {
                        const hash = window.location.hash.slice(1);
                        if (hash && hash !== this.currentTab) {
                            this.currentTab = hash;
                        }
                    });
                },

                async loadDefaults() {
                    const res = await fetch('/api/defaults');
                    this.defaults = await res.json();
                    if (this.defaults.defaults?.image_model) {
                        this.form.model_id = this.defaults.defaults.image_model.id;
                    }
                    if (this.defaults.defaults?.recipe_prompt) {
                        this.form.prompt_id = this.defaults.defaults.recipe_prompt.id;
                    }
                    if (this.defaults.defaults?.title_prompt) {
                        this.form.title_prompt_id = this.defaults.defaults.title_prompt.id;
                    }
                    if (this.defaults.defaults?.ingredients_prompt) {
                        this.form.ingredients_prompt_id = this.defaults.defaults.ingredients_prompt.id;
                    }
                    if (this.defaults.defaults?.text_model) {
                        this.experimentForm.model_id = this.defaults.defaults.text_model.id;
                    }
                },

                async loadHistory() {
                    const res = await fetch('/api/generations');
                    const data = await res.json();
                    this.history = data.data || [];
                },

                async loadModels() {
                    const res = await fetch('/api/models');
                    this.models = await res.json();
                },

                async loadPrompts() {
                    const res = await fetch('/api/prompts');
                    this.prompts = await res.json();
                },

                async loadExperiments() {
                    const res = await fetch('/api/experiments');
                    const data = await res.json();
                    this.experiments = data.data || [];
                },

                async runExperiment() {
                    this.experimentLoading = true;
                    this.experimentResult = null;
                    try {
                        const res = await fetch('/api/experiments', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(this.experimentForm)
                        });
                        if (res.ok) {
                            this.experimentResult = await res.json();
                            await this.loadExperiments();
                            this.showToast('Experiment completed!', 'success');
                        } else {
                            const data = await res.json();
                            this.showToast(data.message || 'Experiment failed', 'error');
                        }
                    } catch (e) {
                        this.showToast('Experiment failed', 'error');
                    }
                    this.experimentLoading = false;
                },

                async deleteExperiment(id) {
                    if (!confirm('Delete this experiment?')) return;
                    await fetch(`/api/experiments/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                    await this.loadExperiments();
                },

                async loadLogs() {
                    const res = await fetch('/api/admin/logs');
                    const data = await res.json();
                    this.logs = data.data || [];
                },

                async loadUsers() {
                    const res = await fetch('/api/admin/users');
                    this.users = await res.json();
                },

                async saveUser() {
                    const res = await fetch('/api/admin/users', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify(this.userForm)
                    });
                    if (res.ok) {
                        this.showUserForm = false;
                        this.userForm = { name: '', email: '', password: '', role: 'operator' };
                        await this.loadUsers();
                    } else {
                        const data = await res.json();
                        this.showToast(data.message || 'Failed to create user', 'error');
                    }
                },

                async toggleUserActive(user) {
                    const res = await fetch(`/api/admin/users/${user.id}/toggle-active`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                    if (res.ok) {
                        const data = await res.json();
                        user.is_active = data.is_active;
                    } else {
                        const data = await res.json();
                        this.showToast(data.message || 'Failed to update user', 'error');
                    }
                },

                async deleteUser(id) {
                    if (!confirm('Delete this user?')) return;
                    const res = await fetch(`/api/admin/users/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                    if (res.ok) {
                        await this.loadUsers();
                    } else {
                        const data = await res.json();
                        this.showToast(data.message || 'Failed to delete user', 'error');
                    }
                },

                async generate() {
                    try {
                        const res = await fetch('/api/generate', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(this.form)
                        });
                        const generation = await res.json();
                        await this.loadHistory();
                        await this.loadQueueStats();
                        this.showToast('Job added to queue', 'success');

                        // Poll for completion
                        if (generation.status === 'processing') {
                            this.pollStatus(generation.id);
                        }
                    } catch (e) {
                        this.showToast('Generation failed', 'error');
                    }
                },

                async pollStatus(generationId) {
                    const poll = async () => {
                        try {
                            const res = await fetch(`/api/generations/${generationId}/status`);
                            const data = await res.json();

                            if (data.status === 'completed' || data.status === 'failed') {
                                // Fetch full generation data
                                const fullRes = await fetch(`/api/generations/${generationId}`);
                                this.result = await fullRes.json();
                                await this.loadHistory();
                                await this.loadQueueStats();

                                if (data.status === 'completed') {
                                    this.showToast('Generation completed!', 'success');
                                } else {
                                    this.showToast('Generation failed', 'error');
                                }
                            } else {
                                await this.loadQueueStats();
                                setTimeout(poll, 2000);
                            }
                        } catch (e) {}
                    };
                    poll();
                },

                async loadQueueStats() {
                    try {
                        const res = await fetch('/api/queue/stats');
                        this.queueStats = await res.json();
                    } catch (e) {}
                },

                showToast(message, type = 'success') {
                    this.toast = { message, type };
                    setTimeout(() => this.toast = null, 4000);
                },

                async toggleShare(gen) {
                    const res = await fetch(`/api/generations/${gen.id}/share`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                    const data = await res.json();
                    gen.is_public = data.is_public;
                    if (data.share_url) {
                        await this.copyToClipboard(data.share_url);
                        this.showToast('Share URL copied to clipboard!', 'success');
                    }
                },

                viewGeneration(gen) {
                    this.result = gen;
                    this.currentTab = 'generate';
                },

                async deleteGeneration(id) {
                    if (!confirm('Delete this generation?')) return;
                    await fetch(`/api/generations/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                    await this.loadHistory();
                },

                async saveModel() {
                    const url = this.editingModel ? `/api/models/${this.editingModel}` : '/api/models';
                    const method = this.editingModel ? 'PUT' : 'POST';
                    await fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify(this.modelForm)
                    });
                    this.showModelForm = false;
                    this.editingModel = null;
                    this.modelForm = { name: '', provider_id: '', type: 'image', is_default: false, is_active: true };
                    await this.loadModels();
                    await this.loadDefaults();
                },

                editModel(model) {
                    this.editingModel = model.id;
                    this.modelForm = {
                        name: model.name,
                        provider_id: model.provider_id,
                        type: model.type,
                        is_default: model.is_default,
                        is_active: model.is_active
                    };
                    this.showModelForm = true;
                },

                async toggleModelActive(model) {
                    const res = await fetch(`/api/models/${model.id}/toggle-active`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                    if (res.ok) {
                        const data = await res.json();
                        model.is_active = data.is_active;
                        await this.loadDefaults();
                    }
                },

                async savePrompt() {
                    const url = this.editingPrompt ? `/api/prompts/${this.editingPrompt}` : '/api/prompts';
                    const method = this.editingPrompt ? 'PUT' : 'POST';
                    await fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify(this.promptForm)
                    });
                    this.showPromptForm = false;
                    this.editingPrompt = null;
                    this.promptForm = { name: '', type: 'recipe', content: '', is_default: false, is_active: true };
                    await this.loadPrompts();
                    await this.loadDefaults();
                },

                editPrompt(prompt) {
                    this.editingPrompt = prompt.id;
                    this.promptForm = {
                        name: prompt.name,
                        type: prompt.type,
                        content: prompt.content,
                        is_default: prompt.is_default,
                        is_active: prompt.is_active
                    };
                    this.showPromptForm = true;
                },

                async togglePromptActive(prompt) {
                    const res = await fetch(`/api/prompts/${prompt.id}/toggle-active`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                    if (res.ok) {
                        const data = await res.json();
                        prompt.is_active = data.is_active;
                        await this.loadDefaults();
                    }
                },

                async copyToClipboard(text) {
                    try {
                        await navigator.clipboard.writeText(text);
                    } catch (e) {
                        console.error('Copy failed', e);
                    }
                }
            }
        }
    </script>
</x-app-layout>
