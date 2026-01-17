// public_html/js/messaging.js - COMPLETE PRODUCTION-READY VERSION
class MessagingSystem {
    constructor() {
        this.currentConversationId = null;
        
        // === API BASE CONFIGURATION ===
        // FOR PRODUCTION (use your actual domain):
        this.API_BASE = 'https://learnthefix.com/backend/messaging';
        
        // FOR LOCAL DEVELOPMENT (uncomment one of these):
        // this.API_BASE = 'http://localhost/learnthefix/backend/messaging';
        // this.API_BASE = '/backend/messaging'; // If using relative path
        // === END API CONFIGURATION ===
        
        this.currentUserId = window.currentUserId || 1; // Set by PHP
        this.pollingInterval = null;
        this.conversationPollingInterval = null;
        
        this.init();
    }
    
    init() {
        this.loadConversations();
        this.setupEventListeners();
        
        // Check URL for conversation parameter
        const urlParams = new URLSearchParams(window.location.search);
        const conversationId = urlParams.get('conversation');
        const professionalId = urlParams.get('professional_id');
        const action = urlParams.get('action');
        
        // Handle starting new conversation from message button
        if (action === 'start' && professionalId) {
            this.startNewConversation(professionalId);
        } else if (conversationId) {
            this.selectConversation(conversationId);
        }
    }
    
    startNewConversation(professionalId) {
        fetch(`${this.API_BASE}/start-conversation.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                professional_id: professionalId
            }),
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            if (data.id) {
                // Success - select the conversation
                this.selectConversation(data.id);
                
                // Update URL
                const url = new URL(window.location);
                url.searchParams.delete('action');
                url.searchParams.delete('professional_id');
                url.searchParams.set('conversation', data.id);
                window.history.replaceState({}, '', url);
            } else {
                alert('Error: ' + (data.error || 'Failed to start conversation'));
                this.loadConversations();
            }
        })
        .catch(error => {
            console.error('Error starting conversation:', error);
            alert('Failed to start conversation. Please try again.');
            this.loadConversations();
        });
    }
    
    loadConversations() {
        fetch(`${this.API_BASE}/conversations.php`, {
            credentials: 'include'
        })
        .then(response => {
            if (response.status === 401) {
                window.location.href = 'login.php';
                return;
            }
            return response.json();
        })
        .then(conversations => {
            this.displayConversations(conversations);
            
            // Schedule next conversation list refresh (every 15 seconds)
            if (this.conversationPollingInterval) {
                clearTimeout(this.conversationPollingInterval);
            }
            this.conversationPollingInterval = setTimeout(() => {
                this.loadConversations();
            }, 15000);
        })
        .catch(error => {
            console.error('Error loading conversations:', error);
            if (document.getElementById('conversationsList')) {
                document.getElementById('conversationsList').innerHTML = 
                    '<div class="loading">Error loading conversations</div>';
            }
        });
    }
    
    displayConversations(conversations) {
        const container = document.getElementById('conversationsList');
        if (!container) return;
        
        if (!conversations || conversations.length === 0) {
            container.innerHTML = '<div class="loading">No conversations yet. Start one by messaging a pro!</div>';
            return;
        }
        
        let html = '';
        conversations.forEach(conv => {
            const time = this.formatTime(conv.last_message_at);
            const preview = conv.last_message_content ? 
                conv.last_message_content.substring(0, 30) + 
                (conv.last_message_content.length > 30 ? '...' : '') : 
                'No messages yet';
            
            const unread = conv.unread_count_user > 0 ? 
                `<span class="badge">${conv.unread_count_user}</span>` : '';
            
            html += `
                <div class="conversation-item" data-id="${conv.id}">
                    <div class="conversation-name">
                        ${conv.professional?.full_name || 'Professional'}
                        ${unread}
                    </div>
                    <div class="conversation-preview">${preview}</div>
                    <div class="conversation-time">${time}</div>
                </div>
            `;
        });
        
        container.innerHTML = html;
        
        // Add click handlers
        container.querySelectorAll('.conversation-item').forEach(item => {
            item.addEventListener('click', () => {
                const conversationId = item.getAttribute('data-id');
                this.selectConversation(conversationId);
                
                // Update URL without reload
                const url = new URL(window.location);
                url.searchParams.set('conversation', conversationId);
                window.history.pushState({}, '', url);
            });
        });
        
        // Highlight active conversation
        if (this.currentConversationId) {
            const activeItem = container.querySelector(`[data-id="${this.currentConversationId}"]`);
            if (activeItem) {
                activeItem.classList.add('active');
            }
        }
    }
    
    selectConversation(conversationId) {
        // Stop any existing polling
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
        }
        
        this.currentConversationId = conversationId;
        
        // Update UI - highlight selected conversation
        document.querySelectorAll('.conversation-item').forEach(item => {
            item.classList.remove('active');
            if (item.getAttribute('data-id') == conversationId) {
                item.classList.add('active');
            }
        });
        
        // Load messages
        this.loadMessages(conversationId);
        
        // Show message input
        const inputContainer = document.querySelector('.message-input-container');
        if (inputContainer) {
            inputContainer.style.display = 'block';
        }
        
        // Update header
        this.updateChatHeader(conversationId);
        
        // Start polling for new messages in this conversation
        this.startMessagePolling(conversationId);
    }
    
    loadMessages(conversationId) {
        fetch(`${this.API_BASE}/get-messages.php?conversation_id=${conversationId}`, {
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.displayMessages(data.messages);
            } else {
                console.error('Error loading messages:', data.error);
                const container = document.getElementById('messagesContainer');
                if (container) {
                    container.innerHTML = '<div class="no-messages">Error loading messages</div>';
                }
            }
        })
        .catch(error => {
            console.error('Error loading messages:', error);
            const container = document.getElementById('messagesContainer');
            if (container) {
                container.innerHTML = '<div class="no-messages">Error loading messages</div>';
            }
        });
    }
    
    displayMessages(messages) {
        const container = document.getElementById('messagesContainer');
        if (!container) return;
        
        if (!messages || messages.length === 0) {
            container.innerHTML = '<div class="no-messages">No messages yet. Start the conversation!</div>';
            return;
        }
        
        let html = '';
        messages.forEach(msg => {
            const time = this.formatTime(msg.created_at);
            const isSent = msg.sender_id == this.currentUserId;
            
            html += `
                <div class="message ${isSent ? 'sent' : 'received'}">
                    <div class="message-content">${this.escapeHtml(msg.message || msg.content)}</div>
                    <div class="message-time">${time}</div>
                </div>
            `;
        });
        
        container.innerHTML = html;
        
        // Scroll to bottom
        container.scrollTop = container.scrollHeight;
    }
    
    sendMessage() {
        const input = document.getElementById('messageInput');
        if (!input || !this.currentConversationId) return;
        
        const content = input.value.trim();
        if (!content) return;
        
        // Disable input while sending
        input.disabled = true;
        const sendButton = document.querySelector('#messageForm button');
        if (sendButton) {
            const originalButtonText = sendButton.textContent;
            sendButton.textContent = 'Sending...';
        }
        
        fetch(`${this.API_BASE}/send-message.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                conversation_id: this.currentConversationId,
                message: content
            }),
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            // Re-enable input
            input.disabled = false;
            if (sendButton) {
                sendButton.textContent = 'Send';
            }
            
            if (data.success) {
                // Clear input
                input.value = '';
                
                // Reload messages immediately
                this.loadMessages(this.currentConversationId);
                
                // Reload conversations list
                this.loadConversations();
            } else {
                alert('Error: ' + (data.error || 'Failed to send message'));
            }
        })
        .catch(error => {
            console.error('Error sending message:', error);
            alert('Failed to send message. Please try again.');
            
            // Re-enable input
            input.disabled = false;
            if (sendButton) {
                sendButton.textContent = 'Send';
            }
        });
    }
    
    startMessagePolling(conversationId) {
        // Clear any existing interval
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
        }
        
        // Poll for new messages every 3 seconds
        this.pollingInterval = setInterval(() => {
            this.loadMessages(conversationId);
        }, 3000);
    }
    
    updateChatHeader(conversationId) {
        const header = document.getElementById('chatHeader');
        if (!header) return;
        
        // Fetch conversation details to show in header
        fetch(`${this.API_BASE}/conversations.php`, {
            credentials: 'include'
        })
        .then(response => response.json())
        .then(conversations => {
            const conversation = conversations.find(c => c.id == conversationId);
            if (conversation) {
                header.innerHTML = `
                    <div class="chat-header">
                        <h3>${conversation.professional?.full_name || 'Professional'}</h3>
                        <small>${conversation.professional?.profession || ''}</small>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading conversation details:', error);
        });
    }
    
    setupEventListeners() {
        const form = document.getElementById('messageForm');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.sendMessage();
            });
        }
        
        // Allow pressing Enter to send (but Shift+Enter for new line)
        const messageInput = document.getElementById('messageInput');
        if (messageInput) {
            messageInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
        }
        
        // Handle browser back/forward buttons
        window.addEventListener('popstate', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const conversationId = urlParams.get('conversation');
            if (conversationId && conversationId !== this.currentConversationId) {
                this.selectConversation(conversationId);
            }
        });
    }
    
    formatTime(dateString) {
        if (!dateString) return '';
        
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;
        
        if (diff < 60000) { // Less than 1 minute
            return 'Just now';
        } else if (diff < 3600000) { // Less than 1 hour
            const minutes = Math.floor(diff / 60000);
            return `${minutes}m ago`;
        } else if (diff < 86400000) { // Today
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        } else if (diff < 172800000) { // Yesterday
            return 'Yesterday ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        } else {
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
    }
    
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', () => {
    window.messaging = new MessagingSystem();
    
    // Set current user ID from PHP (if available)
    if (typeof currentUserId !== 'undefined') {
        window.messaging.currentUserId = currentUserId;
    }
});
