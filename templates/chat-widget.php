<?php
/**
 * Chat Widget Template
 *
 * @package WordPress_AI_Assistant
 * @var array $atts Shortcode attributes
 */

if (!defined('ABSPATH')) {
    exit;
}

$position_class = 'at-ai-chat-position-' . esc_attr($atts['position']);
$theme_class = 'at-ai-chat-theme-' . esc_attr($atts['theme']);
$is_rtl = is_rtl() ? 'rtl' : 'ltr';
?>

<div class="at-ai-chat-wrapper" data-position="<?php echo esc_attr($atts['position']); ?>">
    <?php if ($atts['position'] !== 'inline'): ?>
        <!-- Floating toggle button -->
        <button class="at-ai-chat-toggle-btn" aria-label="<?php esc_attr_e('Toggle AI Chat', 'wordpress-ai-assistant'); ?>">
            <span class="dashicons dashicons-format-chat"></span>
        </button>
    <?php endif; ?>
    
    <!-- Chat container -->
    <div class="at-ai-chat-container <?php echo esc_attr($position_class . ' ' . $theme_class); ?>" 
         style="<?php echo $atts['position'] === 'inline' ? 'height: ' . esc_attr($atts['height']) . '; width: ' . esc_attr($atts['width']) . ';' : ''; ?>"
         data-theme="<?php echo esc_attr($atts['theme']); ?>"
         dir="<?php echo esc_attr($is_rtl); ?>">
        
        <!-- Chat header -->
        <div class="at-ai-chat-header">
            <h3 class="at-ai-chat-title">
                <span class="dashicons dashicons-format-chat"></span>
                <?php echo esc_html($atts['title']); ?>
            </h3>
            <div class="at-ai-chat-actions">
                <button class="at-ai-chat-action-btn at-ai-chat-export" 
                        title="<?php esc_attr_e('Export Chat', 'wordpress-ai-assistant'); ?>">
                    <span class="dashicons dashicons-download"></span>
                </button>
                <button class="at-ai-chat-action-btn at-ai-chat-clear" 
                        title="<?php esc_attr_e('Clear Chat', 'wordpress-ai-assistant'); ?>">
                    <span class="dashicons dashicons-trash"></span>
                </button>
                <?php if ($atts['position'] !== 'inline'): ?>
                <button class="at-ai-chat-action-btn at-ai-chat-minimize" 
                        title="<?php esc_attr_e('Minimize', 'wordpress-ai-assistant'); ?>">
                    <span class="dashicons dashicons-minus"></span>
                </button>
                <button class="at-ai-chat-action-btn at-ai-chat-close" 
                        title="<?php esc_attr_e('Close', 'wordpress-ai-assistant'); ?>">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Messages area -->
        <div class="at-ai-chat-messages">
            <!-- Messages will be inserted here dynamically -->
            
            <!-- Typing indicator -->
            <div class="at-ai-typing-indicator">
                <span class="at-ai-typing-dot"></span>
                <span class="at-ai-typing-dot"></span>
                <span class="at-ai-typing-dot"></span>
            </div>
        </div>
        
        <!-- Input area -->
        <div class="at-ai-chat-input-area">
            <div class="at-ai-chat-input-wrapper">
                <textarea class="at-ai-chat-input" 
                          placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
                          rows="1"
                          dir="<?php echo esc_attr($is_rtl); ?>"></textarea>
            </div>
            <button class="at-ai-chat-send-btn" 
                    title="<?php esc_attr_e('Send Message', 'wordpress-ai-assistant'); ?>">
                <span class="dashicons dashicons-arrow-<?php echo is_rtl() ? 'left' : 'right'; ?>-alt"></span>
            </button>
        </div>
        
        <!-- Loading indicator -->
        <div class="at-ai-chat-loading"></div>
    </div>
</div>

<style>
/* Position-specific styles */
<?php if ($atts['position'] === 'fixed-bottom-right'): ?>
.at-ai-chat-container {
    position: fixed;
    bottom: 80px;
    <?php echo is_rtl() ? 'left' : 'right'; ?>: 20px;
    display: none;
}
<?php elseif ($atts['position'] === 'fixed-bottom-left'): ?>
.at-ai-chat-container {
    position: fixed;
    bottom: 80px;
    <?php echo is_rtl() ? 'right' : 'left'; ?>: 20px;
    display: none;
}
<?php endif; ?>

/* Theme-specific overrides */
<?php if ($atts['theme'] === 'dark'): ?>
.at-ai-chat-theme-dark {
    --chat-bg-color: #1e1e1e;
    --chat-secondary-color: #2d2d2d;
    --chat-text-color: #ffffff;
}
<?php elseif ($atts['theme'] === 'auto'): ?>
@media (prefers-color-scheme: dark) {
    .at-ai-chat-theme-auto {
        --chat-bg-color: #1e1e1e;
        --chat-secondary-color: #2d2d2d;
        --chat-text-color: #ffffff;
    }
}
<?php endif; ?>
</style>