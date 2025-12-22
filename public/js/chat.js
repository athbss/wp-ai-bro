/**
 * AI Chat JavaScript
 * Handles chat functionality with Hebrew/RTL support
 */

(function($) {
    'use strict';

    // Chat Manager Class
    class AIChat {
        constructor() {
            this.container = null;
            this.messagesContainer = null;
            this.input = null;
            this.sendBtn = null;
            this.isOpen = false;
            this.isTyping = false;
            this.currentContext = 'general';
            
            this.init();
        }

        init() {
            // Wait for DOM ready
            $(document).ready(() => {
                this.setupElements();
                this.bindEvents();
                this.loadHistory();
                
                // Auto open if configured
                if (at_ai_chat.settings.auto_open) {
                    this.open();
                }
                
                // Show welcome message
                if (this.messagesContainer && this.messagesContainer.children().length === 0) {
                    this.showWelcomeMessage();
                }
            });
        }

        setupElements() {
            this.container = $('.at-ai-chat-container');
            if (this.container.length === 0) {
                return;
            }
            
            this.messagesContainer = this.container.find('.at-ai-chat-messages');
            this.input = this.container.find('.at-ai-chat-input');
            this.sendBtn = this.container.find('.at-ai-chat-send-btn');
            this.typingIndicator = this.container.find('.at-ai-typing-indicator');
            
            // Setup toggle button if exists
            this.toggleBtn = $('.at-ai-chat-toggle-btn');
            
            // Setup admin bar toggle
            this.adminBarToggle = $('#wp-admin-bar-at-ai-chat');
        }

        bindEvents() {
            if (!this.container) return;
            
            // Send message
            this.sendBtn.on('click', () => this.sendMessage());
            
            // Enter key to send (Shift+Enter for new line)
            this.input.on('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
            
            // Auto-resize input
            this.input.on('input', () => this.autoResizeInput());
            
            // Toggle chat
            if (this.toggleBtn) {
                this.toggleBtn.on('click', () => this.toggle());
            }
            
            // Admin bar toggle
            if (this.adminBarToggle) {
                this.adminBarToggle.on('click', (e) => {
                    e.preventDefault();
                    this.toggle();
                });
            }
            
            // Header actions
            this.container.find('.at-ai-chat-minimize').on('click', () => this.minimize());
            this.container.find('.at-ai-chat-clear').on('click', () => this.clearHistory());
            this.container.find('.at-ai-chat-export').on('click', () => this.exportHistory());
            this.container.find('.at-ai-chat-close').on('click', () => this.close());
            
            // Message actions
            this.container.on('click', '.at-ai-message-copy', (e) => {
                this.copyMessage($(e.currentTarget).closest('.at-ai-message'));
            });
            
            this.container.on('click', '.at-ai-message-regenerate', (e) => {
                this.regenerateMessage($(e.currentTarget).closest('.at-ai-message'));
            });
            
            // Detect context
            this.detectContext();
        }

        detectContext() {
            // Detect current page context
            if ($('body').hasClass('single-post')) {
                this.currentContext = 'post';
            } else if ($('body').hasClass('page')) {
                this.currentContext = 'page';
            } else if ($('body').hasClass('single-product')) {
                this.currentContext = 'product';
            }
        }

        sendMessage() {
            const message = this.input.val().trim();
            if (!message || this.isTyping) {
                return;
            }
            
            // Add user message to chat
            this.addMessage(message, 'user');
            
            // Clear input
            this.input.val('').trigger('input');
            
            // Show typing indicator
            this.showTyping();
            
            // Disable send button
            this.sendBtn.prop('disabled', true);
            
            // Send AJAX request
            $.ajax({
                url: at_ai_chat.ajax_url,
                type: 'POST',
                data: {
                    action: 'at_ai_chat_send_message',
                    nonce: at_ai_chat.nonce,
                    message: message,
                    context: this.currentContext,
                    language: at_ai_chat.settings.language || 'he'
                },
                success: (response) => {
                    if (response.success) {
                        this.addMessage(response.data.response, 'assistant', response.data.timestamp);
                        
                        // Play sound if enabled
                        if (at_ai_chat.settings.sound_enabled) {
                            this.playNotificationSound();
                        }
                    } else {
                        this.showError(response.data || at_ai_chat.strings.error);
                    }
                },
                error: () => {
                    this.showError(at_ai_chat.strings.connection_error);
                },
                complete: () => {
                    this.hideTyping();
                    this.sendBtn.prop('disabled', false);
                }
            });
        }

        addMessage(content, role, timestamp) {
            const messageHtml = this.createMessageHtml(content, role, timestamp);
            this.messagesContainer.append(messageHtml);
            this.scrollToBottom();
        }

        createMessageHtml(content, role, timestamp) {
            const time = timestamp ? this.formatTime(timestamp) : this.formatTime(new Date());
            const processedContent = at_ai_chat.settings.enable_markdown ? 
                this.processMarkdown(content) : this.escapeHtml(content);
            
            let messageHtml = `
                <div class="at-ai-message ${role}">
                    <div class="at-ai-message-bubble">
                        <div class="at-ai-message-content">${processedContent}</div>
                        ${at_ai_chat.settings.show_timestamp ? `<span class="at-ai-message-time">${time}</span>` : ''}
                    </div>
                    <div class="at-ai-message-actions">
                        <button class="at-ai-message-action at-ai-message-copy">
                            <span class="dashicons dashicons-clipboard"></span>
                        </button>`;
            
            if (role === 'user') {
                messageHtml += `
                        <button class="at-ai-message-action at-ai-message-regenerate">
                            <span class="dashicons dashicons-update"></span>
                        </button>`;
            }
            
            messageHtml += `
                    </div>
                </div>`;
            
            return messageHtml;
        }

        showWelcomeMessage() {
            const welcomeHtml = `
                <div class="at-ai-welcome-message">
                    ${at_ai_chat.strings.welcome_message}
                </div>`;
            this.messagesContainer.html(welcomeHtml);
        }

        showTyping() {
            this.isTyping = true;
            this.typingIndicator.addClass('active');
            this.scrollToBottom();
        }

        hideTyping() {
            this.isTyping = false;
            this.typingIndicator.removeClass('active');
        }

        showError(message) {
            const errorHtml = `
                <div class="at-ai-error-message">
                    ${message}
                </div>`;
            this.messagesContainer.append(errorHtml);
            this.scrollToBottom();
            
            // Remove error after 5 seconds
            setTimeout(() => {
                this.messagesContainer.find('.at-ai-error-message').fadeOut(() => {
                    $(this).remove();
                });
            }, 5000);
        }

        scrollToBottom() {
            if (this.messagesContainer) {
                this.messagesContainer.scrollTop(this.messagesContainer[0].scrollHeight);
            }
        }

        autoResizeInput() {
            if (this.input) {
                this.input.css('height', 'auto');
                const newHeight = Math.min(this.input[0].scrollHeight, 120);
                this.input.css('height', newHeight + 'px');
            }
        }

        toggle() {
            if (this.isOpen) {
                this.close();
            } else {
                this.open();
            }
        }

        open() {
            this.isOpen = true;
            this.container.removeClass('chat-closing').addClass('chat-opening');
            this.container.show();
            
            if (this.toggleBtn) {
                this.toggleBtn.addClass('active');
                this.toggleBtn.find('.dashicons')
                    .removeClass('dashicons-format-chat')
                    .addClass('dashicons-no-alt');
            }
            
            // Focus input
            setTimeout(() => {
                this.input.focus();
            }, 300);
            
            // Save state
            this.saveState('open');
        }

        close() {
            this.isOpen = false;
            this.container.removeClass('chat-opening').addClass('chat-closing');
            
            setTimeout(() => {
                this.container.hide();
            }, 300);
            
            if (this.toggleBtn) {
                this.toggleBtn.removeClass('active');
                this.toggleBtn.find('.dashicons')
                    .removeClass('dashicons-no-alt')
                    .addClass('dashicons-format-chat');
            }
            
            // Save state
            this.saveState('closed');
        }

        minimize() {
            this.close();
        }

        clearHistory() {
            if (!confirm(at_ai_chat.strings.clear_confirm)) {
                return;
            }
            
            $.ajax({
                url: at_ai_chat.ajax_url,
                type: 'POST',
                data: {
                    action: 'at_ai_chat_clear_history',
                    nonce: at_ai_chat.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.messagesContainer.empty();
                        this.showWelcomeMessage();
                    }
                }
            });
        }

        exportHistory() {
            $.ajax({
                url: at_ai_chat.ajax_url,
                type: 'POST',
                data: {
                    action: 'at_ai_chat_export_history',
                    nonce: at_ai_chat.nonce,
                    format: 'txt'
                },
                success: (response) => {
                    if (response.success) {
                        this.downloadFile(response.data.data, response.data.filename);
                        this.showNotification(at_ai_chat.strings.export_success);
                    }
                }
            });
        }

        loadHistory() {
            $.ajax({
                url: at_ai_chat.ajax_url,
                type: 'POST',
                data: {
                    action: 'at_ai_chat_get_history',
                    nonce: at_ai_chat.nonce
                },
                success: (response) => {
                    if (response.success && response.data.history.length > 0) {
                        this.messagesContainer.empty();
                        response.data.history.forEach(msg => {
                            this.addMessage(msg.content, msg.role, msg.timestamp);
                        });
                    }
                }
            });
        }

        copyMessage(messageElement) {
            const content = messageElement.find('.at-ai-message-content').text();
            this.copyToClipboard(content);
            this.showNotification(at_ai_chat.strings.copy_success);
        }

        regenerateMessage(messageElement) {
            const content = messageElement.find('.at-ai-message-content').text();
            this.input.val(content);
            this.sendMessage();
        }

        copyToClipboard(text) {
            const temp = $('<textarea>');
            $('body').append(temp);
            temp.val(text).select();
            document.execCommand('copy');
            temp.remove();
        }

        downloadFile(content, filename) {
            const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = filename;
            link.click();
        }

        showNotification(message) {
            const notification = $(`<div class="at-ai-notification">${message}</div>`);
            this.container.append(notification);
            
            setTimeout(() => {
                notification.fadeOut(() => notification.remove());
            }, 3000);
        }

        playNotificationSound() {
            // Create and play a simple notification sound
            const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVGAw+jtDu4KFODwpapdLw0Z');
            audio.volume = 0.3;
            audio.play().catch(() => {
                // Silently fail if audio can't be played
            });
        }

        formatTime(timestamp) {
            const date = new Date(timestamp);
            const hours = date.getHours().toString().padStart(2, '0');
            const minutes = date.getMinutes().toString().padStart(2, '0');
            return `${hours}:${minutes}`;
        }

        escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        processMarkdown(text) {
            // Basic markdown processing
            let processed = this.escapeHtml(text);
            
            // Bold
            processed = processed.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            
            // Italic  
            processed = processed.replace(/\*(.*?)\*/g, '<em>$1</em>');
            
            // Code blocks
            processed = processed.replace(/```([\s\S]*?)```/g, '<pre><code>$1</code></pre>');
            
            // Inline code
            processed = processed.replace(/`(.*?)`/g, '<code>$1</code>');
            
            // Line breaks
            processed = processed.replace(/\n/g, '<br>');
            
            return processed;
        }

        saveState(state) {
            try {
                localStorage.setItem('at_ai_chat_state', state);
            } catch (e) {
                // Silently fail if localStorage is not available
            }
        }

        loadState() {
            try {
                return localStorage.getItem('at_ai_chat_state') || 'closed';
            } catch (e) {
                return 'closed';
            }
        }
    }

    // Initialize chat
    window.atAIChat = new AIChat();

})(jQuery);