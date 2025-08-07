<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Capstone AI Assistant</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
@vite('resources/css/app.css')
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    @vite('resources/js/app.js')
</head>
<body class="bg-gray-50 flex h-screen antialiased font-inter" data-controller="loading">

    <!-- Global Loading Overlay -->
    <div data-loading-target="loader" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div class="flex flex-col items-center w-64">
            <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                <div data-loading-target="progressBar" class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
            </div>
            <p class="mt-3 text-white">Loading...</p>
        </div>
    </div>

    <div class="flex-1 flex h-full"
         data-controller="gemini-assistant"
         data-gemini-assistant-csrf-token-value="{{ csrf_token() }}"
         data-gemini-assistant-initial-conv-id-value="{{ $conversationId ?? '' }}"
         data-gemini-assistant-professor-avatar-url-value="{{ $professorAvatarUrl }}"
         data-current-conversation-id="{{ $conversationId ?? '' }}"
         >


        <!-- API Key Modal -->
        <div data-gemini-assistant-target="apiKeyModal" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-8 shadow-2xl w-full max-w-md">
                <h3 class="text-xl font-semibold mb-4">Enter Gemini API Key</h3>
                <p class="text-gray-600 mb-6">To use the AI assistant, please provide your Gemini API key. You can get one from <a href="https://aistudio.google.com/app/apikey" target="_blank" class="text-blue-600 hover:underline">Google AI Studio</a>.</p>
                <div class="relative mb-4">
                    <input type="password" data-gemini-assistant-target="apiKeyInput" data-action="input->gemini-assistant#updateApiKey" placeholder="Enter your API key"
                           class="w-full px-4 py-2 border rounded-lg pr-10 focus:outline-none focus:ring-2"
                           data-gemini-assistant-target="apiKeyInputClass"
                           pattern="[a-zA-Z0-9\-_]+" title="API Key can only contain alphanumeric characters, hyphens, and underscores.">
                    <p data-gemini-assistant-target="apiKeyErrorOutput" hidden class="text-red-500 text-xs mt-1"></p>
                    <button type="button" data-action="click->gemini-assistant#toggleApiKeyVisibility" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500">
                        <template data-gemini-assistant-target="showApiKeyTemplate">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.656-1.423A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m-.983-2.175A5.002 5.002 0 0012 13a5 5 0 110-10 5 5 0 010 10z"/>
                        </svg>
                        </template>
                        <template data-gemini-assistant-target="hideApiKeyTemplate" hidden>
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </template>
                    </button>
                </div>
                <button data-action="click->gemini-assistant#validateAndSaveApiKey"
                           data-gemini-assistant-target="saveApiKeyButton"
                           class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-4 rounded-lg transition-colors duration-200"
                           >
                   <span data-gemini-assistant-target="saveButtonText">Save and Continue</span>
                   <span data-gemini-assistant-target="savingButtonText" hidden>Saving...</span>
               </button>
            </div>
        </div>
        <!-- Enhanced Sidebar -->
        <div class="w-64 bg-gray-900 text-white flex flex-col shadow-2xl sidebar overflow-y-auto">
            <!-- Sidebar Header -->
            <div class="p-6 border-b border-gray-700">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h2 class="text-lg font-semibold">Chats</h2>
                    </div>
                    <a href="#" data-action="click->gemini-assistant#newChat click->loading#show"
                       class="p-2 text-gray-400 hover:text-gray-200 hover:bg-gray-700 rounded-lg transition-colors relative">
                        <span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </span>
                    </a>
                </div>
            </div>
            
            <!-- Conversation List -->
            <nav class="flex-1 p-4">
                <div class="space-y-2">
                    @forelse($conversations as $conv)
                        <div class="conversation-item rounded-lg cursor-pointer {{ ($conversationId ?? '') == $conv->id ? 'border-blue-500 border' : '' }}">
                            <a href="{{ route('assistant.show', $conv->id) }}" data-action="click->loading#show" class="flex items-center justify-between p-3">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-white truncate">{{ $conv->title }}</p>
                                    <p class="text-xs text-gray-400 mt-1">{{ $conv->updated_at->diffForHumans() }}</p>
                                </div>
                            </a>
                            <button data-action="click->gemini-assistant#deleteConversation" data-conversation-id="{{ $conv->id }}"
                                    class="text-gray-400 hover:text-red-400 p-1 rounded transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                            <form id="delete-conv-{{ $conv->id }}" action="{{ route('conversations.delete', $conv->id) }}" method="POST" style="display: none;">
                                @csrf
                                @method('DELETE')
                            </form>
                        </div>
                    @empty
                        <p class="text-gray-400 text-center py-4">No conversations yet. Start a new chat!</p>
                    @endforelse
                </div>
            </nav>
            
            <!-- User Profile Section -->
            <div class="p-4 border-t border-gray-700">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-gray-600 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-white">{{ Auth::user()->name ?? 'Guest' }}</p>
                        <p class="text-xs text-gray-400">Online</p>
                    </div>
                </div>
                <div class="mt-4 text-xs text-gray-400" data-gemini-assistant-target="maskedApiKeyDisplay" hidden>
                    API Key: <span class="font-mono"></span>
                </div>
                <div class="mt-4">
                    <button data-controller="logout" data-action="logout#logout click->loading#show" class="w-full flex items-center justify-center p-2 text-red-400 hover:text-red-200 hover:bg-red-700 rounded-lg transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        Logout
                    </button>
                </div>
            </div>
        </div>

        <!-- Enhanced Main Chat Area -->
        <div class="flex-1 flex flex-col bg-white">
            <!-- Chat Header -->
            <div class="bg-white border-b border-gray-200 p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3 overflow-hidden">
                            <img src="{{ $professorAvatarUrl }}" alt="Professor Avatar" class="w-full h-full object-cover">
                        </div>
                        <div>
                            <h1 class="text-lg font-semibold text-gray-900">Prof. Johnson</h1>
                            <p class="text-sm text-gray-500">Your AI Academic Mentor • Online</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Messages Area -->
            <div class="flex-1 p-6 overflow-y-auto chat-messages bg-gray-50 relative" data-gemini-assistant-target="chatMessages">
                <div class="max-w-4xl mx-auto space-y-6" data-gemini-assistant-target="messagesContainer">
                    
                </div>
                <!-- Typing Indicator -->
                <div data-gemini-assistant-target="typingIndicator" hidden class="max-w-4xl mx-auto flex justify-start mb-4">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center mr-2 flex-shrink-0 overflow-hidden">
                            <img src="{{ $professorAvatarUrl }}" alt="Professor Avatar" class="w-full h-full object-cover">
                        </div>
                        <div class="bg-white border border-gray-200 p-3 rounded-2xl rounded-bl-md shadow-sm assistant-message-content">
                            <div class="typing-indicator">
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chat Loading Overlay -->
                <div data-gemini-assistant-target="chatLoadingOverlay" hidden class="absolute inset-0 bg-gray-50 bg-opacity-50 flex items-center justify-center z-10">
                    <div class="flex flex-col items-center">
                        <svg class="animate-spin h-10 w-10 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="mt-3 text-gray-700">Loading chat...</p>
                    </div>
                </div>
            </div>

            <!-- Enhanced Input Area -->
            <div class="bg-white border-t border-gray-200 p-4">
                <div class="max-w-4xl mx-auto">
                    <!-- File Previews -->
                    <div data-gemini-assistant-target="filePreviewsContainer" hidden class="mb-4">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3" data-gemini-assistant-target="filePreviews">
                            {{-- File previews will be dynamically added here by Stimulus --}}
                        </div>
                    </div>
                    
                    <!-- Input Container -->
                    <div class="relative">
                        <input type="file" data-gemini-assistant-target="fileInput" data-action="change->gemini-assistant#handleFileSelect" class="hidden" multiple>
                        <div class="flex items-center bg-gray-50 border border-gray-300 rounded-xl p-2 focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500 transition-colors">
                            <button data-action="click->gemini-assistant#triggerFileInput" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-200 rounded-lg transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                </svg>
                            </button>
                            <textarea
                                    data-gemini-assistant-target="promptInput"
                                    data-action="keydown.enter->gemini-assistant#handleEnter @paste->gemini-assistant#handlePaste input->gemini-assistant#updatePrompt"
                                    placeholder="Consult with Professor Johnson..."
                                    class="flex-1 px-3 py-2 bg-transparent border-none focus:outline-none text-gray-900 placeholder-gray-500 resize-none max-h-32 overflow-y-auto"
                                    rows="1"
                            ></textarea>
                            <button data-action="click->gemini-assistant#handleEnter"
                                    data-gemini-assistant-target="askGeminiButton"
                                    class="p-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors relative">
                                <span data-gemini-assistant-target="askButtonText">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                    </svg>
                                </span>
                                <span data-gemini-assistant-target="askButtonSpinner" class="hidden absolute inset-0 flex items-center justify-center">
                                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Footer Text -->
                    <p class="text-xs text-gray-500 text-center mt-3">
                        AI can make mistakes. Please verify important information.
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
    {{-- This comment is added to force a redeployment on Render.com --}}
</html>
