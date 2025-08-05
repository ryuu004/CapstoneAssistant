function geminiAssistant(csrfToken, initialConversationId = null) {
    return {
        prompt: '',
        messages: [],
        files: [],
        isUploading: false,
        conversations: [],
        currentConversationId: initialConversationId, // Initialize with ID from URL
        isTyping: false,
 
        init() {
            if (this.currentConversationId) {
                // If an ID is provided in the URL, load that specific conversation
                this.loadConversation(this.currentConversationId);
            }
            this.fetchConversations(); // Always fetch conversations to populate sidebar
 
            // Watch for changes to the messages array and scroll down
            this.$watch('messages', () => {
                this.$nextTick(() => {
                    const chatContainer = this.$el.querySelector('.chat-messages');
                    if (chatContainer) {
                        chatContainer.scrollTop = chatContainer.scrollHeight;
                    }
                });
            });
        },

        async fetchConversations() {
            try {
                const res = await fetch('/conversations', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    }
                });
                if (!res.ok) {
                    throw new Error('Failed to fetch conversations');
                }
                const data = await res.json();
                this.conversations = data.conversations;

                if (this.conversations.length > 0) {
                    // Check for an existing empty chat first
                    const existingEmptyChat = this.conversations.find(conv => conv.messages_count === 0);
                    if (existingEmptyChat) {
                        this.loadConversation(existingEmptyChat.id);
                    } else {
                        // Load the most recent conversation if no empty chat is found
                        this.loadConversation(this.conversations[0].id);
                    }
                } else {
                    // If no conversations exist, only create a new one if it's the initial load
                    // and there isn't an active conversation already.
                    // This prevents creating a new chat on refresh after deleting all.
                    if (!this.currentConversationId) { // Only create if no current conversation is loaded
                        this.startNewConversation();
                    }
                }
            } catch (error) {
                console.error('Error fetching conversations:', error);
                // Optionally, display an error message to the user
            }
        },

        async startNewConversation() {
            // Check if there's an existing empty chat. If so, load it instead of creating a new one.
            const existingEmptyChat = this.conversations.find(conv => conv.messages_count === 0);
            if (existingEmptyChat) {
                this.loadConversation(existingEmptyChat.id);
                return;
            }

            try {
                const res = await fetch('/conversations/new', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ title: 'New Chat' }) // Initial title
                });
                if (!res.ok) {
                    throw new Error('Failed to create new conversation');
                }
                const data = await res.json();
                this.currentConversationId = data.conversation_id;
                this.messages = []; // Clear messages for new conversation
                this.prompt = ''; // Clear prompt
                this.files = []; // Clear files
                if (this.$refs.fileInput) {
                    this.$refs.fileInput.value = '';
                }
                // Update URL to root if it's the very first conversation, otherwise to its UUID
                if (this.currentConversationId === initialConversationId && !initialConversationId) {
                    window.history.pushState({}, '', '/');
                } else {
                    window.history.pushState({}, '', `/${this.currentConversationId}`);
                }
                await this.fetchConversations(); // Refresh conversation list to show the new chat
            } catch (error) {
                console.error('Error starting new conversation:', error);
                // Optionally, display an error message to the user
            }
        },

        async loadConversation(conversationId) {
            try {
                const res = await fetch(`/conversations/${conversationId}/messages`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    }
                });
                if (!res.ok) {
                    throw new Error('Failed to load conversation messages');
                }
                const data = await res.json();
                this.messages = data.messages.map(msg => {
                    const files = msg.file_ids ? msg.file_ids.map(fileId => {
                        // For loaded messages, file.url will be null unless you implement
                        // a way to retrieve the file from the server for display.
                        // For now, only display name and type.
                        return {
                            name: fileId, // Assuming fileId is enough for display
                            type: 'application/octet-stream', // Generic type
                            url: null // No direct URL for display from history without re-upload
                        };
                    }) : [];
                    return {
                        role: msg.role,
                        content: msg.content,
                        files: files,
                    };
                });
                this.currentConversationId = conversationId;
                this.prompt = ''; // Clear prompt
                this.files = []; // Clear files
                if (this.$refs.fileInput) {
                    this.$refs.fileInput.value = '';
                }
                window.history.pushState({}, '', `/${conversationId}`); // Update URL
            } catch (error) {
                console.error('Error loading conversation:', error);
                // Optionally, display an error message to the user
            }
        },

        async deleteConversation(conversationId) {
            if (!confirm('Are you sure you want to delete this conversation?')) {
                return;
            }
            try {
                const res = await fetch(`/conversations/${conversationId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    }
                });
                if (!res.ok) {
                    throw new Error('Failed to delete conversation');
                }
                await this.fetchConversations(); // Refresh conversation list
                if (this.currentConversationId === conversationId) {
                    this.currentConversationId = null;
                    this.messages = [];
                    this.prompt = '';
                    this.files = [];
                    if (this.$refs.fileInput) {
                        this.$refs.fileInput.value = '';
                    }
                    if (this.conversations.length > 0) {
                        // After deleting, load the most recent conversation
                        this.loadConversation(this.conversations[0].id);
                    } else {
                        // If no conversations left, redirect to home
                        window.location.reload(); // Full page reload to home
                    }
                }
            } catch (error) {
                console.error('Error deleting conversation:', error);
                // Optionally, display an error message to the user
            }
        },
        
        handlePaste(event) {
            const items = (event.clipboardData || window.clipboardData).items;
            for (const item of items) {
                if (item.type.indexOf('image') !== -1) {
                    const blob = item.getAsFile();
                    const file = new File([blob], "pasted-image.png", { type: blob.type });
                    this.files.push(file);
                }
            }
            if (this.files.length > 0) {
                event.preventDefault();
            }
        },

        handleFileSelect(event) {
            for (const file of event.target.files) {
                this.files.push(file);
            }
        },

        removeFile(index) {
            this.files.splice(index, 1);
        },

        handleEnter(event) {
            if (event.shiftKey) {
                this.prompt += '\n';
                this.$nextTick(() => {
                    const textarea = this.$el.querySelector('textarea');
                    textarea.style.height = 'auto';
                    textarea.style.height = textarea.scrollHeight + 'px';
                });
            } else {
                this.askGemini();
            }
        },

        async askGemini() {
            if (this.prompt.trim() === '' && this.files.length === 0) return;
            if (!this.currentConversationId) {
                // If no conversation is selected, force a new one to be created
                await this.startNewConversation();
            }

            const filesToUpload = [...this.files]; // Create a copy of the files to upload
            const userMessage = {
                role: 'user',
                content: this.prompt,
                files: filesToUpload.map(file => ({ // Use the copied array to build the message
                    name: file.name,
                    type: file.type,
                    url: file.type.startsWith('image/') ? URL.createObjectURL(file) : null
                }))
            };

            this.messages.push(userMessage);
            this.prompt = ''; // Clear the text prompt
            this.files = []; // Clear the file previews from the UI
            if (this.$refs.fileInput) {
                this.$refs.fileInput.value = '';
            }

            this.isTyping = true;
            // The placeholder for the assistant's response is now handled by the isTyping flag

            const fileIds = [];

            if (filesToUpload.length > 0) { // Check the copied array for files to upload
                this.isUploading = true;
                try {
                    for (const file of filesToUpload) { // Loop over the copied array
                        const fileFormData = new FormData();
                        fileFormData.append('file', file);

                        const res = await fetch('/upload-file', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
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
                    if (this.$refs.fileInput) {
                       this.$refs.fileInput.value = '';
                    }
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
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        prompt: userMessage.content,
                        fileIds: fileIds,
                        conversation_id: this.currentConversationId
                    })
                });

                if (!res.ok) {
                    const errorData = await res.json();
                    throw new Error(errorData.error || 'Network response was not ok');
                }

                const data = await res.json();
                const assistantResponse = data.output || 'Sorry, something went wrong.';
                const returnedConversationId = data.conversation_id;

                // Update current conversation ID if a new one was created by the backend
                if (returnedConversationId && this.currentConversationId !== returnedConversationId) {
                    this.currentConversationId = returnedConversationId;
                }

                // Load messages for the current conversation to ensure history is updated
                await this.loadConversation(this.currentConversationId);
                
                // Update the title of the current conversation in the sidebar if it's still 'New Chat'
                const currentConvIndex = this.conversations.findIndex(conv => conv.id === this.currentConversationId);
                if (currentConvIndex !== -1 && this.conversations[currentConvIndex].title === 'New Chat' && userMessage.content.trim() !== '') {
                    // Call backend to update title using AI
                    try {
                        const titleRes = await fetch('/conversations/title', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify({
                                conversation_id: this.currentConversationId,
                                user_prompt: userMessage.content
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
                // Files are already cleared, so no need to do it again here.
            }
        }
    }
}