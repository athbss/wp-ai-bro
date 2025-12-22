<?php
/**
 * Test script to check chat status
 * Run this from the WordPress root directory
 */

// Load WordPress
if (!defined('ABSPATH')) {
    $wp_load = dirname(__FILE__) . '/../../../wp-load.php';
    if (file_exists($wp_load)) {
        require_once($wp_load);
    } else {
        die('Cannot find wp-load.php');
    }
}

echo "\n=== WordPress AI Assistant Chat Status ===\n\n";

// Check if plugin is active
if (!function_exists('at_ai_assistant_get_option')) {
    echo "❌ Plugin is not active or function not found\n";
    exit;
}

// Check chat settings
$chat_enabled = at_ai_assistant_get_option('chat_enabled', false);
$chat_enabled_in_admin = at_ai_assistant_get_option('chat_enabled_in_admin', true);
$chat_auto_show = at_ai_assistant_get_option('chat_auto_show_floating', true);

echo "Chat Enabled: " . ($chat_enabled ? '✅ Yes' : '❌ No') . "\n";
echo "Chat in Admin: " . ($chat_enabled_in_admin ? '✅ Yes' : '❌ No') . "\n";
echo "Auto Show Floating: " . ($chat_auto_show ? '✅ Yes' : '❌ No') . "\n";

// Check if files exist
$files_to_check = [
    'includes/features/class-ai-chat.php',
    'includes/features/class-ai-chat-simple.php',
    'public/css/chat.css',
    'public/js/chat.js'
];

echo "\n=== File Status ===\n";
foreach ($files_to_check as $file) {
    $full_path = dirname(__FILE__) . '/' . $file;
    if (file_exists($full_path)) {
        echo "✅ $file exists\n";
    } else {
        echo "❌ $file missing\n";
    }
}

// Check if simple chat class is loaded
echo "\n=== Class Loading ===\n";
if (class_exists('AT_AI_Chat_Simple')) {
    echo "✅ AT_AI_Chat_Simple class is loaded\n";
} else {
    echo "❌ AT_AI_Chat_Simple class is NOT loaded\n";
}

if (class_exists('AT_AI_Chat')) {
    echo "✅ AT_AI_Chat class is loaded\n";
} else {
    echo "❌ AT_AI_Chat class is NOT loaded\n";
}

// Check current user capabilities
echo "\n=== User Permissions ===\n";
if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    echo "Current user: " . $current_user->user_login . "\n";
    echo "Can manage options: " . (current_user_can('manage_options') ? '✅ Yes (Admin)' : '❌ No') . "\n";
} else {
    echo "❌ No user logged in\n";
}

echo "\n";