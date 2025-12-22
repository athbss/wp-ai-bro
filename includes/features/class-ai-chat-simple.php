<?php
/**
 * Simple AI Chat Loader
 * 
 * This is a simplified version to ensure chat loads properly
 */

if (!defined('ABSPATH')) {
    exit;
}

class AT_AI_Chat_Simple {
    
    public function __construct() {
        // Always add the chat to footer if enabled
        add_action('wp_footer', array($this, 'render_chat'));
        add_action('admin_footer', array($this, 'render_chat'));
    }
    
    public function render_chat() {
        // Check if chat is enabled
        if (!at_ai_assistant_get_option('chat_enabled', false)) {
            return;
        }
        
        // Check if user is admin (only show for administrators)
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Check if we should show in admin
        if (is_admin() && !at_ai_assistant_get_option('chat_enabled_in_admin', true)) {
            return;
        }
        
        ?>
        <!-- AI Chat Widget -->
        <style>
            .at-simple-chat-button {
                position: fixed;
                bottom: 20px;
                right: 20px;
                width: 60px;
                height: 60px;
                background: #0073aa;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                z-index: 999999;
                transition: transform 0.3s;
            }
            
            .at-simple-chat-button:hover {
                transform: scale(1.1);
            }
            
            .at-simple-chat-button svg {
                width: 30px;
                height: 30px;
                fill: white;
            }
            
            .rtl .at-simple-chat-button {
                right: auto;
                left: 20px;
            }
            
            .at-simple-chat-container {
                position: fixed;
                bottom: 90px;
                right: 20px;
                width: 370px;
                height: 500px;
                background: white;
                border-radius: 10px;
                box-shadow: 0 5px 20px rgba(0,0,0,0.15);
                display: none;
                z-index: 999998;
                flex-direction: column;
            }
            
            .rtl .at-simple-chat-container {
                right: auto;
                left: 20px;
            }
            
            .at-simple-chat-container.open {
                display: flex;
            }
            
            .at-simple-chat-header {
                background: #0073aa;
                color: white;
                padding: 15px;
                border-radius: 10px 10px 0 0;
                font-weight: bold;
            }
            
            .at-simple-chat-messages {
                flex: 1;
                padding: 15px;
                overflow-y: auto;
            }
            
            .at-simple-chat-input-area {
                padding: 15px;
                border-top: 1px solid #ddd;
                display: flex;
                gap: 10px;
            }
            
            .at-simple-chat-input {
                flex: 1;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 20px;
            }
            
            .at-simple-chat-send {
                background: #0073aa;
                color: white;
                border: none;
                border-radius: 50%;
                width: 36px;
                height: 36px;
                cursor: pointer;
            }
        </style>
        
        <div class="at-simple-chat-button" onclick="toggleSimpleChat()">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
            </svg>
        </div>
        
        <div class="at-simple-chat-container" id="at-simple-chat">
            <div class="at-simple-chat-header">
                <?php echo at_ai_assistant_get_option('chat_welcome_message', __('עוזר AI - איך אוכל לעזור?', 'wordpress-ai-assistant')); ?>
            </div>
            <div class="at-simple-chat-messages">
                <div style="text-align: center; color: #666; padding: 20px;">
                    <?php _e('ברוך הבא! שאל אותי כל שאלה...', 'wordpress-ai-assistant'); ?>
                </div>
            </div>
            <div class="at-simple-chat-input-area">
                <input type="text" class="at-simple-chat-input" placeholder="<?php _e('הקלד הודעה...', 'wordpress-ai-assistant'); ?>">
                <button class="at-simple-chat-send">➤</button>
            </div>
        </div>
        
        <script>
            function toggleSimpleChat() {
                var chat = document.getElementById('at-simple-chat');
                chat.classList.toggle('open');
            }
            
            // Basic chat functionality
            document.addEventListener('DOMContentLoaded', function() {
                var input = document.querySelector('.at-simple-chat-input');
                var button = document.querySelector('.at-simple-chat-send');
                var messages = document.querySelector('.at-simple-chat-messages');
                
                function sendMessage() {
                    var message = input.value.trim();
                    if (!message) return;
                    
                    // Add user message
                    var userMsg = document.createElement('div');
                    userMsg.style.cssText = 'text-align: right; margin: 10px 0; padding: 8px 12px; background: #0073aa; color: white; border-radius: 15px; display: inline-block; max-width: 70%; margin-left: auto;';
                    userMsg.textContent = message;
                    messages.appendChild(userMsg);
                    
                    // Clear input
                    input.value = '';
                    
                    // Show typing indicator
                    var typing = document.createElement('div');
                    typing.style.cssText = 'text-align: left; margin: 10px 0; color: #666; font-style: italic;';
                    typing.textContent = '<?php _e("AI מקליד...", "wordpress-ai-assistant"); ?>';
                    messages.appendChild(typing);
                    
                    // Scroll to bottom
                    messages.scrollTop = messages.scrollHeight;
                    
                    // Send to server
                    fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=at_ai_chat_send_message&message=' + encodeURIComponent(message) + '&nonce=<?php echo wp_create_nonce("at_ai_chat_nonce"); ?>'
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Remove typing indicator
                        messages.removeChild(typing);
                        
                        // Add AI response
                        var aiMsg = document.createElement('div');
                        aiMsg.style.cssText = 'text-align: left; margin: 10px 0; padding: 8px 12px; background: #f1f1f1; color: #333; border-radius: 15px; display: inline-block; max-width: 70%;';
                        aiMsg.textContent = data.success ? data.data.response : '<?php _e("מצטער, אירעה שגיאה", "wordpress-ai-assistant"); ?>';
                        messages.appendChild(aiMsg);
                        
                        // Scroll to bottom
                        messages.scrollTop = messages.scrollHeight;
                    })
                    .catch(error => {
                        // Remove typing indicator
                        messages.removeChild(typing);
                        
                        // Show error
                        var errorMsg = document.createElement('div');
                        errorMsg.style.cssText = 'text-align: center; margin: 10px 0; color: red;';
                        errorMsg.textContent = '<?php _e("שגיאת חיבור", "wordpress-ai-assistant"); ?>';
                        messages.appendChild(errorMsg);
                    });
                }
                
                if (button) {
                    button.addEventListener('click', sendMessage);
                }
                
                if (input) {
                    input.addEventListener('keypress', function(e) {
                        if (e.key === 'Enter') {
                            sendMessage();
                        }
                    });
                }
            });
        </script>
        <?php
    }
}