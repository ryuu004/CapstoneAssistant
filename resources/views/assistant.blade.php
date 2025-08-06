<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Capstone AI Assistant</title>
    <meta http-equiv="Content-Security-Policy" content="script-src 'self' 'unsafe-inline' 'unsafe-eval' https://unpkg.com https://cdn.tailwindcss.com https://cdn.jsdelivr.net http://localhost:8000 https://capstoneassistant.onrender.com https://www.google.com/recaptcha/ https://www.gstatic.com/recaptcha/ blob:; style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; connect-src 'self' https://generativelanguage.googleapis.com http://localhost:8000 https://capstoneassistant.onrender.com https://www.google.com/recaptcha/ https://www.gstatic.com/recaptcha/;">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="{{ asset('js/assistant.js') }}" defer></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        /* Custom scrollbar styling */
        .chat-messages::-webkit-scrollbar {
            width: 6px;
        }
        .chat-messages::-webkit-scrollbar-track {
            background: #f8fafc;
        }
        .chat-messages::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        .chat-messages::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        .sidebar::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar::-webkit-scrollbar-track {
            background: #374151;
        }
        .sidebar::-webkit-scrollbar-thumb {
            background: #6b7280;
            border-radius: 2px;
        }
        
        /* Smooth animations */
        .message-bubble {
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Hover effects */
        .conversation-item:hover {
            background: rgba(75, 85, 99, 0.8);
            transition: all 0.2s ease;
        }
        
        .file-preview {
            transition: all 0.2s ease;
        }
        .file-preview:hover {
            transform: scale(1.02);
        }
        
        /* Loading animation */
        .typing-indicator {
            display: inline-flex;
            align-items: center;
        }
        .typing-indicator span {
            height: 8px;
            width: 8px;
            border-radius: 50%;
            background: #9ca3af;
            margin: 0 1px;
            animation: typing 1.4s infinite ease-in-out;
        }
        .typing-indicator span:nth-child(1) { animation-delay: -0.32s; }
        .typing-indicator span:nth-child(2) { animation-delay: -0.16s; }
        
        @keyframes typing {
            0%, 80%, 100% {
                transform: scale(0.8);
                opacity: 0.5;
            }
            40% {
                transform: scale(1);
                opacity: 1;
            }
        }
    </style>
</head>
<body class="bg-gray-50 flex h-screen antialiased font-inter">
    <div class="flex-1 flex h-full" x-data="geminiAssistantComponent('{{ csrf_token() }}', null)">
        <!-- API Key Modal -->
        <div x-show="isApiKeyNeeded" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-8 shadow-2xl w-full max-w-md">
                <h3 class="text-xl font-semibold mb-4">Enter Gemini API Key</h3>
                <p class="text-gray-600 mb-6">To use the AI assistant, please provide your Gemini API key. You can get one from <a href="https://aistudio.google.com/app/apikey" target="_blank" class="text-blue-600 hover:underline">Google AI Studio</a>.</p>
                <div class="relative mb-4">
                    <input :type="showApiKey ? 'text' : 'password'" x-model="apiKey" placeholder="Enter your API key"
                           class="w-full px-4 py-2 border rounded-lg pr-10 focus:outline-none focus:ring-2"
                           :class="{'border-red-500 focus:ring-red-500': apiKeyError, 'border-gray-300 focus:ring-blue-500': !apiKeyError}"
                           pattern="[a-zA-Z0-9\-_]+" title="API Key can only contain alphanumeric characters, hyphens, and underscores.">
                    <p x-show="apiKeyError" x-text="apiKeyError" class="text-red-500 text-xs mt-1"></p>
                    <button type="button" @click="toggleApiKeyVisibility" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500">
                        <template x-if="showApiKey">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.656-1.423A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m-.983-2.175A5.002 5.002 0 0012 13a5 5 0 110-10 5 5 0 010 10z"/>
                            </svg>
                        </template>
                        <template x-if="!showApiKey">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </template>
                    </button>
                </div>
                <button @click="validateAndSaveApiKey"
                           :disabled="isSavingApiKey"
                           class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-4 rounded-lg transition-colors duration-200"
                           :class="{'opacity-50 cursor-not-allowed': isSavingApiKey}">
                   <span x-show="!isSavingApiKey">Save and Continue</span>
                   <span x-show="isSavingApiKey">Saving...</span>
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
                </div>
                <button @click="startNewConversation" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-4 rounded-lg text-sm transition-colors duration-200 flex items-center justify-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    New Chat
                </button>
            </div>
            
            <!-- Conversation List -->
            <nav class="flex-1 p-4">
                <div class="space-y-2">
                    <template x-for="conversation in conversations" :key="conversation.id">
                        <div class="conversation-item rounded-lg cursor-pointer" 
                             :class="{'bg-gray-700': currentConversationId === conversation.id}"
                             @click="loadConversation(conversation.id); window.history.pushState({}, '', `/${conversation.id}`)">
                            <div class="flex items-center justify-between p-3">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-white truncate" x-text="conversation.title"></p>
                                    <p class="text-xs text-gray-400 mt-1">2 hours ago</p>
                                </div>
                                <button @click.stop="deleteConversation(conversation.id)" 
                                        class="text-gray-400 hover:text-red-400 p-1 rounded transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </template>
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
                        <p class="text-sm font-medium text-white">Student</p>
                        <p class="text-xs text-gray-400">Online</p>
                    </div>
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
                            <img src="{{ asset('images/professor_avatar.jpg') }}" alt="Professor Avatar" class="w-full h-full object-cover">
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
            <div class="flex-1 p-6 overflow-y-auto chat-messages bg-gray-50">
                <div class="max-w-4xl mx-auto space-y-6">
                    <!-- Welcome Message -->
                    <div class="text-center py-8">
                        <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 overflow-hidden">
                            <img src="{{ asset('images/professor_avatar.jpg') }}" alt="Professor Avatar" class="w-full h-full object-cover">
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Welcome! I am Professor Johnson.</h3>
                        <p class="text-gray-600">I am here to guide you with your capstone project. Huwag mahiyang magtanong, but make sure your questions are well-thought-out. Let's begin.</p>
                    </div>
                    
                    <template x-for="(message, index) in messages" :key="index">
                        <div class="message-bubble">
                            <template x-if="message.role === 'user'">
                                <div class="flex justify-end mb-4">
                                    <div class="flex items-end max-w-xs lg:max-w-md">
                                        <div class="bg-blue-600 text-white p-3 rounded-2xl rounded-br-md shadow-lg">
                                            <template x-if="message.files && message.files.length > 0">
                                                <div class="mb-2 grid gap-2" :class="{'grid-cols-2': message.files.length > 1}">
                                                    <template x-for="file in message.files" :key="file.name">
                                                        <div>
                                                            <template x-if="file.type.startsWith('image/')">
                                                                <img :src="file.url" class="rounded-lg max-h-48 w-full object-cover">
                                                            </template>
                                                            <template x-if="!file.type.startsWith('image/')">
                                                                <div class="p-2 rounded-lg bg-blue-500 text-white flex items-center">
                                                                    <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                                    </svg>
                                                                    <span class="text-sm truncate" x-text="file.name"></span>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                            <p class="text-sm whitespace-pre-wrap" x-text="message.content" x-show="message.content.trim() !== ''"></p>
                                        </div>
                                        <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center ml-2 flex-shrink-0">
                                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            
                            <template x-if="message.role === 'assistant'">
                                <div class="flex justify-start mb-4">
                                    <div class="flex items-end max-w-xs lg:max-w-2xl">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center mr-2 flex-shrink-0 overflow-hidden">
                                            <img src="{{ asset('images/professor_avatar.jpg') }}" alt="Professor Avatar" class="w-full h-full object-cover">
                                        </div>
                                        <div class="bg-white border border-gray-200 p-3 rounded-2xl rounded-bl-md shadow-sm">
                                            <div class="text-sm text-gray-800 whitespace-pre-wrap prose" x-html="marked.parse(message.content)"></div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                    
                    <!-- Typing Indicator -->
                    <div x-show="isTyping" class="flex justify-start mb-4">
                        <div class="flex items-end">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center mr-2 flex-shrink-0 overflow-hidden">
                                <img src="{{ asset('images/professor_avatar.jpg') }}" alt="Professor Avatar" class="w-full h-full object-cover">
                            </div>
                            <div class="bg-white border border-gray-200 p-3 rounded-2xl rounded-bl-md shadow-sm">
                                <div class="typing-indicator">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Input Area -->
            <div class="bg-white border-t border-gray-200 p-4">
                <div class="max-w-4xl mx-auto">
                    <!-- File Previews -->
                    <div x-show="files.length > 0" class="mb-4">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <template x-for="(file, index) in files" :key="index">
                                <div class="file-preview relative bg-gray-50 border border-gray-200 rounded-lg p-3">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 flex-shrink-0 mr-3">
                                            <template x-if="file.type.startsWith('image/')">
                                                <img :src="URL.createObjectURL(file)" class="w-full h-full rounded-md object-cover">
                                            </template>
                                            <template x-if="!file.type.startsWith('image/')">
                                                <div class="w-full h-full rounded-md bg-blue-100 flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                </div>
                                            </template>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate" x-text="file.name"></p>
                                            <p class="text-xs text-gray-500" x-text="`${(file.size / 1024).toFixed(1)} KB`"></p>
                                        </div>
                                    </div>
                                    <button @click="removeFile(index)" class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 transition-colors">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                    
                    <!-- Input Container -->
                    <div class="relative">
                        <input type="file" x-ref="fileInput" @change="handleFileSelect" class="hidden" multiple>
                        <div class="flex items-center bg-gray-50 border border-gray-300 rounded-xl p-2 focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500 transition-colors">
                            <button @click="$refs.fileInput.click()" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-200 rounded-lg transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                </svg>
                            </button>
                            <textarea
                                    x-model="prompt"
                                    @keydown.enter.prevent="handleEnter"
                                    @paste="handlePaste"
                                    placeholder="Consult with Professor Johnson..."
                                    class="flex-1 px-3 py-2 bg-transparent border-none focus:outline-none text-gray-900 placeholder-gray-500 resize-none max-h-32 overflow-y-auto"
                                    rows="1"
                                    x-init="$watch('prompt', () => { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px' })"
                            ></textarea>
                            <button @click="askGemini" 
                                    :disabled="!prompt.trim() && files.length === 0"
                                    class="p-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
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
    <script>
        // Global click listener for debugging
        document.addEventListener('click', function(event) {
            console.log('Global Click Event:', event.target);
            if (event.target.closest('button')) {
                console.log('Clicked button:', event.target.closest('button').outerHTML);
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const chatMessages = document.querySelector('.chat-messages');
            if (chatMessages) {
                chatMessages.addEventListener('click', function(event) {
                    const target = event.target;
                    // Check if the clicked element is an anchor tag within a prose div
                    if (target.tagName === 'A' && target.closest('.prose')) {
                        target.setAttribute('target', '_blank');
                        target.setAttribute('rel', 'noopener noreferrer');
                    }
                });
            }
        });

        // Explicitly start Alpine after all scripts are loaded
    </script>
</html>
