import { Controller } from "@hotwired/stimulus";
import DOMPurify from 'dompurify';
import AuthVisibilityController from "./auth_visibility_controller"
import LoadingController from "./loading_controller";


export default class extends Controller {
    static targets = ["fileInput", "chatMessages", "apiKeyModal", "apiKeyInput", "apiKeyErrorOutput", "showApiKeyTemplate", "hideApiKeyTemplate", "saveApiKeyButton", "saveButtonText", "savingButtonText", "typingIndicator", "filePreviewsContainer", "filePreviews", "promptInput", "askGeminiButton", "maskedApiKeyDisplay", "askButtonText", "askButtonSpinner", "chatLoadingOverlay", "messagesContainer"];
    static values = { csrfToken: String, initialConvId: String, prompt: String, files: Array, isUploading: Boolean, isTyping: Boolean, apiKey: String, isApiKeyNeeded: Boolean, showApiKey: Boolean, apiKeyError: String, isSavingApiKey: Boolean, professorAvatarUrl: String, maskedApiKey: String, isChatLoading: Boolean, loaded: Boolean, initialMessages: Array };
    static controllers = ["loading"];

    messageHasFiles = (message) => {
        return message.files && message.files.length > 0;
    }

        toggleForm(e) {
        e.preventDefault()
        this.registerFormTarget.classList.toggle("hidden")
        this.loginFormTarget.classList.toggle("hidden")
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
        if (!this.loadedValue) { // Add a guard to prevent redundant calls on subsequent connects
            console.log('[connect] Calling load()...');
            this.load();
            this.loadedValue = true; // Set a flag to indicate that load has been called
        }
    }

    async load() {
        console.log('[load] Function called. Setting isChatLoading to true.');
        this.isChatLoading = true; // Set loading state
        const storedApiKey = localStorage.getItem('gemini_api_key');
        console.log('[load] Stored API Key:', storedApiKey);
        if (!storedApiKey || storedApiKey.trim() === '') {
            console.log('[Load] No API key found or it is empty. Showing modal.');
            this.isApiKeyNeeded = true;
            requestAnimationFrame(() => {
                this.apiKeyModalTarget.style.display = 'flex';
                console.log('[Load - requestAnimationFrame] API Key modal display set to "flex".');
            });
        } else {
            console.log('[Load] API key found. Hiding modal.');
            this.apiKey = storedApiKey;
            this.isApiKeyNeeded = false;
            this.apiKeyModalTarget.style.display = 'none';
        }
        console.log('[Load] apiKeyModalTarget.style.display after initial load logic:', this.apiKeyModalTarget.style.display);
        // Set the masked API key on load if an API key exists
        if (this.apiKey) {
            this.maskedApiKey = this.maskApiKey(this.apiKey);
        }

        const convId = this.initialConvIdValue;
        console.log(`[load] convId: ${convId}, isApiKeyNeeded: ${this.isApiKeyNeeded}`);
        if (!convId) {
            console.log('[load] No conversation ID. Hiding loading.');
            this.isChatLoading = false; // Ensure loading is hidden if no convId
            this.displayWelcomeMessage(); // Display welcome message for new chats
            return;
        }

        try {
            console.log('[load] Fetching messages for conversation:', convId);
            const res = await fetch(`/assistant/${convId}/messages`);
            const data = await res.json();
            console.log('[load] Messages fetched successfully. Data:', data);

            this.messagesContainerTarget.innerHTML = ''; // clear existing

            data.messages.forEach(message => {
                this.addMessageToContainer(message);
            });
            this.scrollChatToBottom();
        } catch (e) {
            console.error('Failed to load messages:', e);
        } finally {
            console.log('[load] Finally block. Setting isChatLoading to false.');
            this.isChatLoading = false; // Ensure loading is hidden after load attempt
        }
    }

    async loadMessages() {
        console.log('[loadMessages] Function called. Setting isChatLoading to true.');
        this.isChatLoading = true; // Set loading state
        const convId = this.initialConvIdValue;
        if (!convId) {
            console.log('[loadMessages] No conversation ID. Hiding loading.');
            this.isChatLoading = false; // Ensure loading is hidden if no convId
            return;
        }

        try {
            console.log('[loadMessages] Fetching messages for conversation:', convId);
            const res = await fetch(`/assistant/${convId}/messages`);
            const data = await res.json();
            console.log('[loadMessages] Messages fetched successfully. Data:', data);

            // Remove any temporary messages before loading new ones from the backend
            const tempMessages = this.messagesContainerTarget.querySelectorAll('[id^="temp-"]');
            tempMessages.forEach(msg => msg.remove());

            this.messagesContainerTarget.innerHTML = ''; // clear existing
            data.messages.forEach(message => {
                this.addMessageToContainer(message);
            });
            this.scrollChatToBottom();
            this.application.getControllerForElementAndIdentifier(document.body, "loading").hide();
        } catch (e) {
            console.error('[loadMessages] Failed to load messages:', e);
            this.application.getControllerForElementAndIdentifier(document.body, "loading").hide();
        } finally {
            console.log('[loadMessages] Finally block. Setting isChatLoading to false.');
            this.isChatLoading = false; // Ensure loading is hidden after load attempt
        }
    }

    // Custom getters/setters for data values to interact with Stimulus data attributes
    get prompt() { return this.promptInputValue; }
    set prompt(value) { this.promptInputValue = value; }


    get files() { return this.filesValue; }
    set files(value) { this.filesValue = value; }

    get isUploading() { return this.isUploadingValue; }
    set isUploading(value) { this.isUploadingValue = value; }



    get isTyping() { return this.isTypingValue; }
    set isTyping(value) {
        this.isTypingValue = value;
        this.typingIndicatorTarget.hidden = !value; // Toggle visibility of typing indicator
        this.askGeminiButtonTarget.disabled = value; // Disable ask button when typing
        this.askButtonTextTarget.hidden = value; // Hide button text
        this.askButtonSpinnerTarget.hidden = !value; // Show spinner
        if (value) {
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
        console.log(`[isChatLoading setter] Value changed to: ${value}`);
        this.isChatLoadingValue = value;
        if (this.hasChatLoadingOverlayTarget) {
            console.log(`[isChatLoading setter] chatLoadingOverlayTarget exists. Setting hidden to: ${!value}`);
            this.chatLoadingOverlayTarget.hidden = !value;
            if (!value) {
                console.log('[isChatLoading setter] Value is false. Setting timeout to hide overlay.');
                setTimeout(() => {
                    if (this.hasChatLoadingOverlayTarget) { // Check again in case element was removed
                        this.chatLoadingOverlayTarget.hidden = true;
                        console.log('[isChatLoading setter] Overlay hidden after timeout.');
                    }
                }, 500);
            } else {
                console.log('[isChatLoading setter] Value is true. Overlay should be visible.');
            }
        } else {
            console.warn('[isChatLoading setter] chatLoadingOverlayTarget not found!');
        }
    }


    maskApiKey(key) {
        if (!key || key.length <= 8) {
            return key;
        }
        return key.substring(0, 4) + '...' + key.substring(key.length - 4);
    }






    
    newChat(event) {
        event.preventDefault(); // Prevent the default link behavior
        event.currentTarget.disabled = true; // Disable the button immediately to prevent double clicks
        window.location.href = '/assistant/new'; // Redirect to the new conversation route
    }

    deleteConversation(event) {
        event.preventDefault();
        if (confirm('Are you sure you want to delete this conversation?')) {
            const conversationId = event.currentTarget.dataset.conversationId;
            document.getElementById(`delete-conv-${conversationId}`).submit();
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
    if (this.isChatLoading) return; // Prevent duplicate calls
    this.isChatLoading = true; // Set loading state

    if (this.prompt.trim() === '' && this.files.length === 0) {
        this.isChatLoading = false; // Reset loading state if no prompt/files
        return;
    }

    const filesToUpload = [...this.files];
    const userMessageContent = this.prompt; // Store content before clearing prompt
    const tempId = `temp-${Date.now()}`;

    // Show temporary user message
    this.addMessageToContainer({ role: 'user', content: userMessageContent, id: tempId, files: filesToUpload.map(file => ({ name: file.name, type: file.type, url: file.url })) });
    this.scrollChatToBottom();

    this.prompt = '';
    this.promptInputTarget.value = '';
    this.files = [];
    if (this.hasFileInputTarget) {
        this.fileInputTarget.value = '';
    }

    this.isTyping = true;

    const fileIds = [];

    try {
        if (filesToUpload.length > 0) {
            this.isUploading = true;
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
        }

        const res = await fetch(`/ask-gemini/${this.initialConvIdValue || ''}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfTokenValue,
            },
            body: JSON.stringify({
                prompt: userMessageContent, // Use stored content
                fileIds: fileIds,
                api_key: this.apiKey,
                conversation_id: this.initialConvIdValue || null
            })
        });

        if (!res.ok) {
            const errorData = await res.json();
            throw new Error(errorData.error || 'Failed to send message');
        }

        // Wait for backend to save both user + assistant messages
        const data = await res.json();

        // Re-fetch full message thread after backend finishes saving
        await this.loadMessages();

    } catch (error) {
        console.error('Error asking Gemini:', error);
        // Display an error message in the chat if something goes wrong
        this.addMessageToContainer({
            role: 'assistant',
            content: `Error: ${error.message}`,
            files: []
        });
        this.scrollChatToBottom();
    } finally {
        this.isTyping = false;
        this.isUploading = false; // Ensure upload loading is also reset
        this.isChatLoading = false; // Reset chat loading state
    }
}

    addMessageToContainer(message) {
        const messageHtml = `
            <div class="flex ${message.role === 'user' ? 'justify-end' : 'justify-start'} mb-4" ${message.id ? `id="${message.id}"` : ''}>
                ${message.role === 'assistant' ? `
                    <div class="w-8 h-8 rounded-full flex items-center justify-center mr-2 flex-shrink-0 overflow-hidden">
                        <img src="${this.professorAvatarUrlValue}" alt="Professor Avatar" class="w-full h-full object-cover">
                    </div>
                ` : ''}
                <div class="${message.role === 'user' ? 'bg-blue-600 text-white rounded-bl-md' : 'bg-white border border-gray-200 rounded-br-md'} p-3 rounded-2xl shadow-sm max-w-xl">
                    ${message.content ? `<div class="message-content">${this.renderMarkdown(message.content)}</div>` : ''}
                    ${this.messageHasFiles(message) ? `
                        <div class="grid ${this.messageFileGridClass(message)} gap-2 mt-2">
                            ${message.files.map(file => `
                                <div class="relative">
                                    ${this.isImageFile(file) ? `<img src="${file.url}" class="rounded-lg max-w-full h-auto">` : `
                                        <div class="w-full h-24 bg-gray-100 rounded-lg flex items-center justify-center">
                                            <span class="text-gray-500 text-xs truncate">${file.name}</span>
                                        </div>
                                    `}
                                </div>
                            `).join('')}
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
        this.messagesContainerTarget.insertAdjacentHTML('beforeend', messageHtml);
    }

    async validateAndSaveApiKey() {
        console.log('validateAndSaveApiKey function called. - DEBUG');
        console.log('Current API Key:', this.apiKey);
        this.isSavingApiKey = true;
        this.apiKeyError = '';
        console.log('Attempting to validate API key...');
        try {
            // Ensure the API key is not empty before sending the request
            if (!this.apiKey) {
                this.apiKeyError = 'API Key cannot be empty.';
                return;
            }

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
    displayWelcomeMessage() {
        console.log('[displayWelcomeMessage] Function called.');
        const welcomeMessageHtml = `
            <div class="flex flex-col items-center justify-center text-center py-8 px-4">
                <div class="w-24 h-24 rounded-full overflow-hidden mb-4">
                    <img src="${this.professorAvatarUrlValue}" alt="Professor Avatar" class="w-full h-full object-cover">
                </div>
                <h1 class="text-2xl font-semibold text-gray-800 mb-2">Welcome! I am Professor Johnson.</h1>
                <p class="text-gray-600 leading-relaxed max-w-lg">
                    I am here to guide you with your capstone project. Huwag mahiyang magtanong, but make sure your questions are well-
                    thought-out. Let's begin.
                </p>
            </div>
        `;
        this.messagesContainerTarget.innerHTML = welcomeMessageHtml;
    }
}
