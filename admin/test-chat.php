<?php
/**
 * Chat Test Page
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Check permissions
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Handle enable/disable
if (isset($_POST['enable_chat'])) {
    check_admin_referer('at_ai_assistant_test_chat');
    update_option('at_ai_assistant_chat_enabled', true);
    echo '<div class="notice notice-success"><p>' . __('Chat enabled!', 'wordpress-ai-assistant') . '</p></div>';
} elseif (isset($_POST['disable_chat'])) {
    check_admin_referer('at_ai_assistant_test_chat');
    update_option('at_ai_assistant_chat_enabled', false);
    echo '<div class="notice notice-success"><p>' . __('Chat disabled!', 'wordpress-ai-assistant') . '</p></div>';
}

$chat_enabled = at_ai_assistant_get_option('chat_enabled', false);
?>

<div class="wrap">
    <h1><?php _e('AI Chat Test', 'wordpress-ai-assistant'); ?></h1>
    
    <div class="card">
        <h2><?php _e('Chat Status', 'wordpress-ai-assistant'); ?></h2>
        <p>
            <strong><?php _e('Current Status:', 'wordpress-ai-assistant'); ?></strong>
            <?php if ($chat_enabled): ?>
                <span style="color: green;">✅ <?php _e('Enabled', 'wordpress-ai-assistant'); ?></span>
            <?php else: ?>
                <span style="color: red;">❌ <?php _e('Disabled', 'wordpress-ai-assistant'); ?></span>
            <?php endif; ?>
        </p>
        
        <form method="post" action="">
            <?php wp_nonce_field('at_ai_assistant_test_chat'); ?>
            <?php if ($chat_enabled): ?>
                <button type="submit" name="disable_chat" class="button button-secondary">
                    <?php _e('Disable Chat', 'wordpress-ai-assistant'); ?>
                </button>
            <?php else: ?>
                <button type="submit" name="enable_chat" class="button button-primary">
                    <?php _e('Enable Chat', 'wordpress-ai-assistant'); ?>
                </button>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="card">
        <h2><?php _e('Important Information', 'wordpress-ai-assistant'); ?></h2>
        <ul>
            <li>✅ <?php _e('Chat is configured to show only for administrators', 'wordpress-ai-assistant'); ?></li>
            <li>✅ <?php _e('You are logged in as an administrator', 'wordpress-ai-assistant'); ?></li>
            <li><?php 
                if ($chat_enabled) {
                    _e('ℹ️ The chat button should appear in the bottom-right corner (blue circle)', 'wordpress-ai-assistant');
                } else {
                    _e('⚠️ Enable the chat above to see the chat button', 'wordpress-ai-assistant');
                }
            ?></li>
        </ul>
    </div>
    
    <div class="card">
        <h2><?php _e('Debug Information', 'wordpress-ai-assistant'); ?></h2>
        <table class="widefat">
            <tr>
                <th><?php _e('Setting', 'wordpress-ai-assistant'); ?></th>
                <th><?php _e('Value', 'wordpress-ai-assistant'); ?></th>
            </tr>
            <tr>
                <td>Chat Enabled</td>
                <td><?php echo $chat_enabled ? 'true' : 'false'; ?></td>
            </tr>
            <tr>
                <td>Chat in Admin</td>
                <td><?php echo at_ai_assistant_get_option('chat_enabled_in_admin', true) ? 'true' : 'false'; ?></td>
            </tr>
            <tr>
                <td>Auto Show Floating</td>
                <td><?php echo at_ai_assistant_get_option('chat_auto_show_floating', true) ? 'true' : 'false'; ?></td>
            </tr>
            <tr>
                <td>Current User Can Manage Options</td>
                <td><?php echo current_user_can('manage_options') ? 'true (Admin)' : 'false'; ?></td>
            </tr>
            <tr>
                <td>Simple Chat Class Exists</td>
                <td><?php echo class_exists('AT_AI_Chat_Simple') ? 'true' : 'false'; ?></td>
            </tr>
            <tr>
                <td>Full Chat Class Exists</td>
                <td><?php echo class_exists('AT_AI_Chat') ? 'true' : 'false'; ?></td>
            </tr>
        </table>
    </div>
    
    <?php if ($chat_enabled): ?>
    <div class="card">
        <h2><?php _e('Force Show Chat', 'wordpress-ai-assistant'); ?></h2>
        <p><?php _e('If the chat is not appearing, click the button below to force it to show:', 'wordpress-ai-assistant'); ?></p>
        <button onclick="forceShowChat()" class="button button-primary">
            <?php _e('Force Show Chat Window', 'wordpress-ai-assistant'); ?>
        </button>
        
        <script>
        function forceShowChat() {
            // Try to find and click the chat button
            var button = document.querySelector('.at-simple-chat-button');
            if (button) {
                button.click();
                alert('Chat window should now be open!');
            } else {
                // If button doesn't exist, create it manually
                var html = `
                    <div class="at-simple-chat-button" onclick="toggleSimpleChat()" style="position: fixed; bottom: 20px; right: 20px; width: 60px; height: 60px; background: #0073aa; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 10px rgba(0,0,0,0.2); z-index: 999999;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width: 30px; height: 30px; fill: white;">
                            <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
                        </svg>
                    </div>
                `;
                document.body.insertAdjacentHTML('beforeend', html);
                alert('Chat button has been added to the page!');
            }
        }
        </script>
    </div>
    <?php endif; ?>
</div>