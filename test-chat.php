<?php
/**
 * Test page for AI Chat
 * 
 * Create a new page and add this shortcode to test:
 * [ai_chat position="fixed-bottom-right" theme="light"]
 */

// Add this file temporarily to test the chat
// You can delete it after testing

?>
<!DOCTYPE html>
<html dir="rtl" lang="he-IL">
<head>
    <meta charset="UTF-8">
    <title>בדיקת צ'אט AI</title>
    <?php wp_head(); ?>
</head>
<body>
    <div style="padding: 50px; text-align: center;">
        <h1>בדיקת צ'אט AI</h1>
        <p>אם הצ'אט מופעל בהגדרות, אתה אמור לראות כפתור צ'אט בפינה הימנית התחתונה.</p>
        
        <h2>שורטקוד לבדיקה:</h2>
        <pre style="background: #f5f5f5; padding: 20px; border-radius: 5px; direction: ltr;">
[ai_chat position="fixed-bottom-right" theme="light"]
        </pre>
        
        <h2>סטטוס:</h2>
        <ul style="text-align: right;">
            <li>צ'אט מופעל: <?php echo at_ai_assistant_get_option('chat_enabled', false) ? '✅ כן' : '❌ לא'; ?></li>
            <li>מופעל באדמין: <?php echo at_ai_assistant_get_option('chat_enabled_in_admin', true) ? '✅ כן' : '❌ לא'; ?></li>
            <li>מופעל לכולם: <?php echo at_ai_assistant_get_option('chat_enabled_for_all', false) ? '✅ כן' : '❌ לא'; ?></li>
        </ul>
        
        <?php 
        // Test the shortcode
        echo do_shortcode('[ai_chat position="fixed-bottom-right" theme="light"]');
        ?>
    </div>
    
    <?php wp_footer(); ?>
</body>
</html>