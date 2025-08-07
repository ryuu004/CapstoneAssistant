import { Controller } from "@hotwired/stimulus";
import DOMPurify from 'dompurify';

export default class extends Controller {
    static targets = ["fileInput", "chatMessages", "apiKeyModal", "apiKeyInput", "apiKeyErrorOutput", "showApiKeyTemplate", "hideApiKeyTemplate", "saveApiKeyButton", "saveButtonText", "savingButtonText", "conversationsList", "messagesContainer", "typingIndicator", "filePreviewsContainer", "filePreviews", "promptInput", "askGeminiButton", "newChatButton", "maskedApiKeyDisplay", "askButtonText", "askButtonSpinner", "newChatButtonText", "newChatButtonSpinner", "chatLoadingOverlay"];
    static values = { csrfToken: String, initialConvId: String, prompt: String, messages: Array, files: Array, isUploading: Boolean, conversations: Array, currentConversationId: String, isTyping: Boolean, apiKey: String, isApiKeyNeeded: Boolean, showApiKey: Boolean, apiKeyError: String, isSavingApiKey: Boolean, professorAvatarUrl: String, maskedApiKey: String, isChatLoading: Boolean, isLoadingConversation: Boolean };

    messageHasFiles = (message) => {
        return message.files && message.files.length > 0;
    }

    messageFileGridClass = (message) => {
        if (message.files.length === 1) {
            return 'grid-cols-1';
        } else if (message.files.length === 2) {
            return 'grid-cols-2';
        } else {
            return 'grid-cols-3';
        }
    }

    isImageFile = (file) => {
        return file.type.startsWith('image/');
    }

    connect() {
        console.log('Stimulus GeminiAssistantController connected.');
        const storedApiKey = localStorage.getItem('gemini_api_key');
        console.log('[Connect] Stored API Key:', storedApiKey);
        if (!storedApiKey || storedApiKey.trim() === '') {
            console.log('[Connect] No API key found or it is empty. Showing modal.');
            this.isApiKeyNeeded = true;
            requestAnimationFrame(() => {
                this.apiKeyModalTarget.style.display = 'flex';
                console.log('[Connect - requestAnimationFrame] API Key modal display set to "flex".');
            });
        } else {
            console.log('[Connect] API key found. Hiding modal and fetching conversations.');
            this.apiKey = storedApiKey;
            this.isApiKeyNeeded = false;
            this.apiKeyModalTarget.style.display = 'none';
            this.fetchConversations();
        }
        console.log('[Connect] apiKeyModalTarget.style.display after initial connect logic:', this.apiKeyModalTarget.style.display);
        // Set the masked API key on connect if an API key exists
        if (this.apiKey) {
            this.maskedApiKey = this.maskApiKey(this.apiKey);
        }

        if (this.initialConvIdValue) {
            this.loadConversation(this.initialConvIdValue);
        } else {
            this.fetchConversations();
        }
    }

    // Custom getters/setters for data values to interact with Stimulus data attributes
    get prompt() { return this.promptInputValue; }
    set prompt(value) { this.promptInputValue = value; }

    get messages() { return this.messagesValue; }
    set messages(value) { this.messagesValue = value; }

    get files() { return this.filesValue; }
    set files(value) { this.filesValue = value; }

    get isUploading() { return this.isUploadingValue; }
    set isUploading(value) { this.isUploadingValue = value; }

    get conversations() { return this.conversationsValue; }
    set conversations(value) { this.conversationsValue = value; }

    get currentConversationId() { return this.currentConversationIdValue; }
    set currentConversationId(value) { this.currentConversationIdValue = value; }

    get isTyping() { return this.isTypingValue; }
    set isTyping(value) {
        this.isTypingValue = value;
        this.typingIndicatorTarget.hidden = !value; // Toggle visibility of typing indicator
        this.askGeminiButtonTarget.disabled = value; // Disable ask button when typing
        this.askButtonTextTarget.hidden = value; // Hide button text
        this.askButtonSpinnerTarget.hidden = !value; // Show spinner
        if (value) {
            this.scrollChatToBottom();
            this.isChatLoading = false; // Hide chat loading overlay when typing starts
        }
    }

    get apiKey() { return this.apiKeyValue; }
    set apiKey(value) {
        this.apiKeyValue = value;
        this.maskedApiKey = this.maskApiKey(value);
    }

    get isApiKeyNeeded() { return this.isApiKeyNeededValue; }
    set isApiKeyNeeded(value) { this.isApiKeyNeededValue = value; }

    get showApiKey() { return this.showApiKeyValue; }
    set showApiKey(value) {
        this.showApiKeyValue = value;
        this.apiKeyInputTarget.type = value ? 'text' : 'password'; // Toggle input type
        this.showApiKeyTemplateTarget.hidden = !value; // Toggle template visibility
        this.hideApiKeyTemplateTarget.hidden = value; // Toggle template visibility
    }

    get apiKeyError() { return this.apiKeyErrorValue; }
    set apiKeyError(value) {
        this.apiKeyErrorValue = value;
        this.apiKeyErrorOutputTarget.textContent = value;
        this.apiKeyErrorOutputTarget.hidden = !value; // Show/hide error message
        this.apiKeyInputTarget.classList.toggle('border-red-500', !!value);
        this.apiKeyInputTarget.classList.toggle('focus:ring-red-500', !!value);
        this.apiKeyInputTarget.classList.toggle('border-gray-300', !value);
        this.apiKeyInputTarget.classList.toggle('focus:ring-blue-500', !value);
    }

    get isSavingApiKey() { return this.isSavingApiKeyValue; }
    set isSavingApiKey(value) {
        this.isSavingApiKeyValue = value;
        this.saveApiKeyButtonTarget.disabled = value; // Disable button
        this.saveApiKeyButtonTarget.classList.toggle('opacity-50', value);
        this.saveApiKeyButtonTarget.classList.toggle('cursor-not-allowed', value);
        this.saveButtonTextTarget.hidden = value; // Toggle text visibility
        this.savingButtonTextTarget.hidden = !value; // Toggle text visibility
    }

    get maskedApiKey() { return this.maskedApiKeyValue; }
    set maskedApiKey(value) {
        this.maskedApiKeyValue = value;
        if (this.hasMaskedApiKeyDisplayTarget) {
            this.maskedApiKeyDisplayTarget.textContent = value;
            this.maskedApiKeyDisplayTarget.hidden = !value;
        }
    }

    get isChatLoading() { return this.isChatLoadingValue; }
    set isChatLoading(value) {
        this.isChatLoadingValue = value;
        if (this.hasChatLoadingOverlayTarget) {
            this.chatLoadingOverlayTarget.hidden = !value;
        }
    }

    get isLoadingConversation() { return this.isLoadingConversationValue; }
    set isLoadingConversation(value) {
        this.isLoadingConversationValue = value;
        // Optionally, disable conversation list interaction here if needed
        this.renderConversations(); // Re-render conversations to show loading state
    }

    maskApiKey(key) {
        if (!key || key.length <= 8) {
            return key;
        }
        return key.substring(0, 4) + '...' + key.substring(key.length - 4);
    }

    async fetchConversations() {
        this.isChatLoading = true;
        try {
            const res = await fetch('/conversations', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfTokenValue,
                }
            });
            if (!res.ok) {
                throw new Error('Failed to fetch conversations');
            }
            const data = await res.json();
            this.conversations = data.conversations;
            this.renderConversations(); // Render conversations after fetching

            if (this.conversations.length > 0) {
                const existingEmptyChat = this.conversations.find(conv => conv.messages_count === 0);
                if (existingEmptyChat) {
                    this.loadConversation(existingEmptyChat.id);
                } else {
                    this.loadConversation(this.conversations[0].id);
                }
            } else {
                this.startNewConversation();
            }
        } catch (error) {
            console.error('Error fetching conversations:', error);
        } finally {
            this.isChatLoading = false;
        }
    }

    async startNewConversation() {
        console.log('startNewConversation called.');
        this.newChatButtonTarget.disabled = true;
        this.newChatButtonTextTarget.hidden = true;
        this.newChatButtonSpinnerTarget.hidden = false;

        const existingEmptyChat = this.conversations.find(conv => conv.messages_count === 0);
        if (existingEmptyChat) {
            this.loadConversation(existingEmptyChat.id);
            this.newChatButtonTarget.disabled = false;
            this.newChatButtonTextTarget.hidden = false;
            this.newChatButtonSpinnerTarget.hidden = true;
            return;
        }

        try {
            const res = await fetch('/conversations/new', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfTokenValue,
                },
                body: JSON.stringify({ title: 'New Chat' })
            });
            if (!res.ok) {
                throw new Error('Failed to create new conversation');
            }
            const data = await res.json();
            this.currentConversationId = data.conversation_id;
            this.messages = [];
            console.log('Calling displayIntroMessage in startNewConversation.');
            this.displayIntroMessage(); // Add intro message for new conversation
            console.log('Messages after displayIntroMessage in startNewConversation:', this.messages);
            this.prompt = '';
            this.files = [];
            if (this.hasFileInputTarget) {
                this.fileInputTarget.value = '';
            }
            if (this.currentConversationId === this.initialConvIdValue && !this.initialConvIdValue) {
                window.history.pushState({}, '', '/');
            } else {
                window.history.pushState({}, '', `/${this.currentConversationId}`);
            }
            await this.fetchConversations();
        } catch (error) {
            console.error('Error starting new conversation:', error);
        } finally {
            this.newChatButtonTarget.disabled = false;
            this.newChatButtonTextTarget.hidden = false;
            this.newChatButtonSpinnerTarget.hidden = true;
        }
    }

    displayIntroMessage() {
        console.log('displayIntroMessage called.');
        this.messages = [{
            role: 'assistant',
            content: 'Welcome! I am Professor Johnson. I am here to guide you with your capstone project. Huwag mahiyang magtanong, but make sure your questions are well-thought-out. Let\'s begin.'
        }];
        console.log('Intro message added to messages:', this.messages);
    }

    async loadConversation(conversationId) {
        console.log(`Attempting to load conversation: ${conversationId}`);
        this.isChatLoading = true;
        try {
            const res = await fetch(`/conversations/${conversationId}/messages`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfTokenValue,
                }
            });
            if (!res.ok) {
                const errorData = await res.json();
                console.error('Failed to load conversation messages:', errorData);
                throw new Error(errorData.message || 'Failed to load conversation messages');
            }
            const data = await res.json();
            console.log('Conversation messages loaded:', data);
            console.log('Raw messages from API:', data.messages);
            this.messages = data.messages.map(msg => {
                const files = msg.file_ids ? msg.file_ids.map(fileId => {
                    return {
                        id: fileId,
                        name: fileId,
                        type: 'application/octet-stream',
                        url: null,
                        sizeText: 'N/A',
                        rawFile: null
                    };
                }) : [];
                return {
                    role: msg.role,
                    content: msg.content,
                    files: files,
                };
            });
            if (this.messages.length === 0) {
                console.log('Conversation is empty, calling displayIntroMessage.');
                this.displayIntroMessage(); // Add intro message if conversation is empty
            } else {
                console.log('Conversation is not empty, not calling displayIntroMessage.');
            }
            console.log('Messages after loadConversation logic:', this.messages);
            this.currentConversationId = conversationId;
            this.prompt = '';
            this.files = [];
            if (this.hasFileInputTarget) {
                this.fileInputTarget.value = '';
            }
            window.history.pushState({}, '', `/${conversationId}`);
            this.renderMessages(); // Render messages after loading
            this.renderConversations(); // Re-render conversations to update active state
        } catch (error) {
            console.error('Error loading conversation:', error);
        } finally {
            this.isChatLoading = false;
            this.isLoadingConversation = false; // Add this line
        }
    }

    loadConversationAndHistory(event) {
        const conversationId = event.currentTarget.dataset.conversationId;
        this.isLoadingConversation = true; // Set loading state for conversation
        try {
            this.loadConversation(conversationId);
        } finally {
            this.isLoadingConversation = false; // Ensure loading state is reset
        }
        window.history.pushState({}, '', `/${conversationId}`);
    }

    async deleteConversation(event) {
        const conversationId = event.currentTarget.dataset.conversationId;
        if (!confirm('Are you sure you want to delete this conversation?')) {
            return;
        }
        this.isLoadingConversation = true; // Set loading state for conversation
        try {
            const res = await fetch(`/conversations/${conversationId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfTokenValue,
                }
            });
            if (!res.ok) {
                throw new Error('Failed to delete conversation');
            }
            await this.fetchConversations();
            if (this.currentConversationId === conversationId) {
                this.currentConversationId = null;
                this.messages = [];
                this.prompt = '';
                this.files = [];
                if (this.hasFileInputTarget) {
                    this.fileInputTarget.value = '';
                }
                if (this.conversations.length > 0) {
                    this.loadConversation(this.conversations[0].id);
                } else {
                    window.location.reload();
                }
            }
            this.renderConversations();
        } catch (error) {
            console.error('Error deleting conversation:', error);
        } finally {
            this.isLoadingConversation = false; // Reset loading state
        }
    }
    
    updatePrompt(event) {
        this.prompt = event.target.value;
    }

    handlePaste(event) {
        const items = (event.clipboardData || window.clipboardData).items;
        for (const item of items) {
            if (item.type.indexOf('image') !== -1) {
                const blob = item.getAsFile();
                const file = new File([blob], "pasted-image.png", { type: blob.type });
                this.files.push(this.processFile(file));
            }
        }
        if (this.files.length > 0) {
            event.preventDefault();
        }
        this.renderFilePreviews(); // Render file previews after pasting
    }

    handleFileSelect() {
        for (const file of this.fileInputTarget.files) {
            this.files.push(this.processFile(file));
        }
        this.renderFilePreviews(); // Render file previews after selecting
    }

    removeFile(event) {
        const fileToRemoveId = event.currentTarget.dataset.fileId;
        this.files = this.files.filter(file => file.id !== fileToRemoveId);
        this.renderFilePreviews(); // Re-render file previews after removal
    }

    handleEnter(event) {
        if (event.shiftKey) {
            this.prompt += '\n';
            // No direct DOM manipulation for textarea height, rely on CSS or a Stimulus value
        } else {
            this.askGemini();
        }
    }

    async askGemini() {
        if (this.prompt.trim() === '' && this.files.length === 0) return;
        if (!this.currentConversationId) {
            await this.startNewConversation();
        }

        const filesToUpload = [...this.files];
        const userMessage = {
            role: 'user',
            content: this.prompt,
            files: filesToUpload.map(file => ({
                name: file.name,
                type: file.type,
                url: file.url
            }))
        };

        this.messages = [...this.messages, userMessage];
        this.prompt = '';
        this.promptInputTarget.value = '';
        this.files = [];
        if (this.hasFileInputTarget) {
            this.fileInputTarget.value = '';
        }

        this.isTyping = true;
        this.isChatLoading = true; // Show loading overlay
        this.renderMessages(); // Render messages to show user's new message and typing indicator

        const fileIds = [];

        if (filesToUpload.length > 0) {
            this.isUploading = true;
            try {
                for (const file of filesToUpload) {
                    const fileFormData = new FormData();
                    fileFormData.append('file', file.rawFile);

                    const res = await fetch('/upload-file', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': this.csrfTokenValue,
                        },
                        body: fileFormData
                    });

                    if (!res.ok) {
                        const errorData = await res.json();
                        const errorMessage = errorData.errors ? Object.values(errorData.errors).flat().join(' ') : (errorData.message || 'File upload failed');
                        throw new Error(errorMessage);
                    }
                    
                    const data = await res.json();
                    fileIds.push(data.fileId);
                }
            } catch (error) {
                console.error('Error uploading files:', error);
                this.messages[this.messages.length - 1].content = `Sorry, a file upload failed: ${error.message}`;
                this.isUploading = false;
                this.files = [];
                if (this.hasFileInputTarget) {
                    this.fileInputTarget.value = '';
                }
                this.renderMessages(); // Re-render messages to show error
                return;
            } finally {
                this.isUploading = false;
            }
        }

        try {
            const res = await fetch('/ask-gemini', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfTokenValue,
                },
                body: JSON.stringify({
                    prompt: userMessage.content,
                    fileIds: fileIds,
                    conversation_id: this.currentConversationId,
                    api_key: this.apiKey
                })
            });

            if (!res.ok) {
                const errorData = await res.json();
                throw new Error(errorData.error || 'Network response was not ok');
            }

            const data = await res.json();
            const assistantResponse = data.output || 'Sorry, something went wrong.';
            const returnedConversationId = data.conversation_id;

            if (returnedConversationId && this.currentConversationId !== returnedConversationId) {
                this.currentConversationId = returnedConversationId;
            }

            await this.loadConversation(this.currentConversationId);
            
            const currentConvIndex = this.conversations.findIndex(conv => conv.id === this.currentConversationId);
            if (currentConvIndex !== -1 && this.conversations[currentConvIndex].title === 'New Chat' && userMessage.content.trim() !== '') {
                try {
                    const titleRes = await fetch('/conversations/title', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfTokenValue,
                        },
                        body: JSON.stringify({
                            conversation_id: this.currentConversationId,
                            user_prompt: userMessage.content,
                            api_key: this.apiKey
                        })
                    });
                    if (titleRes.ok) {
                        const titleData = await titleRes.json();
                        if (titleData.success) {
                            this.conversations[currentConvIndex].title = titleData.new_title;
                        }
                    } else {
                        console.error('Failed to update conversation title:', await titleRes.json());
                    }
                } catch (titleError) {
                    console.error('Error updating conversation title:', titleError);
                }
            }


        } catch (error) {
            console.error('Error asking Gemini:', error);
            this.messages[this.messages.length - 1].content = `Sorry, an error occurred: ${error.message}`;
        } finally {
            this.isTyping = false;
            this.isChatLoading = false; // Hide loading overlay
            this.renderMessages(); // Re-render messages to hide typing indicator
        }
    }

    async validateAndSaveApiKey() {
        console.log('validateAndSaveApiKey function called.');
        console.log('Current API Key:', this.apiKey);
        this.isSavingApiKey = true;
        this.apiKeyError = '';
        console.log('Attempting to validate API key...');
        try {
            const res = await fetch('/validate-api-key', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfTokenValue,
                },
                body: JSON.stringify({ api_key: this.apiKey })
            });
            if (res.ok) {
                console.log('API Key validated successfully.');
                localStorage.setItem('gemini_api_key', this.apiKey);
                this.isApiKeyNeeded = false;
                this.apiKeyModalTarget.style.display = 'none'; // Hide modal on success
                this.maskedApiKey = this.maskApiKey(this.apiKey); // Update masked key on success
                await this.fetchConversations();
                console.log('API Key modal should now be hidden and conversations fetched.');
            } else {
                const errorData = await res.json();
                console.error('API Key validation failed:', errorData);
                this.apiKeyError = errorData.message || 'Invalid API Key. Please check your key and try again.';
                localStorage.removeItem('gemini_api_key'); // Remove invalid key from localStorage
                this.maskedApiKey = ''; // Clear masked key if validation fails
            }
        } catch (error) {
            console.error('Error during API key validation fetch:', error);
            this.apiKeyError = 'An error occurred while validating the API key.';
            localStorage.removeItem('gemini_api_key'); // Remove key on network error as well
            this.maskedApiKey = ''; // Clear masked key on error
        } finally {
            this.isSavingApiKey = false;
        }
    }

    toggleApiKeyVisibility() {
        console.log('toggleApiKeyVisibility called.');
        this.showApiKey = !this.showApiKey;
        this.apiKeyError = '';
    }

    updateApiKey(event) {
        this.apiKey = event.target.value;
    }

    processFile(file) {
        const id = URL.createObjectURL(file);
        const sizeInKB = (file.size / 1024).toFixed(1);
        return {
            id: id,
            name: file.name,
            type: file.type,
            url: file.type.startsWith('image/') ? URL.createObjectURL(file) : null,
            sizeText: `${sizeInKB} KB`,
            rawFile: file
        };
    }

    scrollChatToBottom() {
        if (this.hasChatMessagesTarget) {
            this.chatMessagesTarget.scrollTop = this.chatMessagesTarget.scrollHeight;
        }
    }

    triggerFileInput() {
        this.fileInputTarget.click();
    }

    get isPromptEmpty() {
        return this.prompt.trim() === '' && this.files.length === 0;
    }

    renderConversations() {
        this.conversationsListTarget.innerHTML = ''; // Clear existing list
        const self = this;
        this.conversations.forEach(conversation => {
            const isActive = this.currentConversationId === conversation.id;
            const isLoading = isActive && this.isLoadingConversation;
            const conversationElement = `
                <div class="conversation-item rounded-lg cursor-pointer ${isActive ? 'border-blue-500 border' : ''} ${isLoading ? 'opacity-75 cursor-wait' : ''}"
                     data-action="click->gemini-assistant#loadConversationAndHistory"
                     data-conversation-id="${conversation.id}">
                    <div class="flex items-center justify-between p-3">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white truncate">${conversation.title}</p>
                            <p class="text-xs text-gray-400 mt-1">2 hours ago</p>
                        </div>
                        ${isLoading ? `
                            <svg class="animate-spin h-4 w-4 text-white ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        ` : `
                            <button data-action="click->gemini-assistant#deleteConversation" data-conversation-id="${conversation.id}"
                                    class="text-gray-400 hover:text-red-400 p-1 rounded transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        `}
                    </div>
                </div>
            `;
            this.conversationsListTarget.insertAdjacentHTML('beforeend', conversationElement);
        });
    }

    renderMessages() {
        this.messagesContainerTarget.innerHTML = ''; // Clear existing messages
        const self = this; // Capture 'this' context
        this.messages.forEach(message => {
            let messageHtml = '';
            if (message.role === 'user') {
                let filePreviewsHtml = '';
                if (self.messageHasFiles(message)) {
                    filePreviewsHtml = `
                        <div class="mb-2 grid gap-2 ${self.messageFileGridClass(message)}">
                            ${message.files.map(file => `
                                <div>
                                    ${self.isImageFile(file) ? `<img src="${file.url}" class="rounded-lg max-h-48 w-full object-cover">` : `
                                        <div class="p-2 rounded-lg bg-blue-500 text-white flex items-center">
                                            <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            <span class="text-sm truncate">${file.name}</span>
                                        </div>
                                    `}
                                </div>
                            `).join('')}
                        </div>
                    `;
                }
                messageHtml = `
                    <div class="message-bubble">
                        <div class="flex justify-end mb-4">
                            <div class="flex items-end max-w-xs lg:max-w-md">
                                <div class="bg-blue-600 text-white p-3 rounded-2xl rounded-br-md shadow-lg">
                                    ${filePreviewsHtml}
                                    ${self.messageHasContent(message) ? `<p class="text-sm whitespace-pre-wrap">${message.content}</p>` : ''}
                                </div>
                                <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center ml-2 flex-shrink-0">
                                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else if (message.role === 'assistant') {
                messageHtml = `
                    <div class="message-bubble">
                        <div class="flex justify-start mb-4">
                            <div class="flex items-end max-w-xs lg:max-w-2xl">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center mr-2 flex-shrink-0 overflow-hidden">
                                    <img src="${self.professorAvatarUrlValue}" alt="Professor Avatar" class="w-full h-full object-cover">
                                </div>
                                <div class="bg-white border border-gray-200 p-3 rounded-2xl rounded-bl-md shadow-sm">
                                    <div class="text-sm text-gray-800 whitespace-pre-wrap prose">${self.renderMarkdown(message.content)}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
            this.messagesContainerTarget.insertAdjacentHTML('beforeend', messageHtml);
        });
        self.scrollChatToBottom();
    }

    messageHasContent = (message) => {
        return message.content && message.content.trim() !== '';
    }



    renderMarkdown = (markdown) => {
        // Sanitize the HTML output from marked.parse using DOMPurify
        return DOMPurify.sanitize(marked.parse(markdown));
    }

    renderFilePreviews() {
        if (this.files.length > 0) {
            this.filePreviewsContainerTarget.hidden = false;
            this.filePreviewsTarget.innerHTML = ''; // Clear existing previews
            this.files.forEach(file => {
                const filePreviewHtml = `
                    <div class="file-preview relative bg-gray-50 border border-gray-200 rounded-lg p-3">
                        <div class="flex items-center">
                            <div class="w-10 h-10 flex-shrink-0 mr-3">
                                ${this.isImageFile(file) ? `<img src="${file.url}" class="w-full h-full rounded-md object-cover">` : `
                                    <div class="w-full h-full rounded-md bg-blue-100 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                `}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">${file.name}</p>
                                <p class="text-xs text-gray-500">${file.sizeText}</p>
                            </div>
                        </div>
                        <button data-action="click->gemini-assistant#removeFile" data-file-id="${file.id}"
                                class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                `;
                this.filePreviewsTarget.insertAdjacentHTML('beforeend', filePreviewHtml);
            });
        } else {
            this.filePreviewsContainerTarget.hidden = true;
        }
    }
}