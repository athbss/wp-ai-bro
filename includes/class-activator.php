<?php

/**
 * Fired during plugin activation
 *
 * @since      1.0.0
 *
 * @package    WordPress_AI_Assistant
 * @subpackage WordPress_AI_Assistant/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    WordPress_AI_Assistant
 * @subpackage WordPress_AI_Assistant/includes
 * @author     Amit Trabelsi <amit@amit-trabelsi.co.il>
 */
class AT_WordPress_AI_Assistant_Activator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function activate() {
        // Create database tables
        self::create_tables();
        
        // Set default options
        self::set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create plugin database tables
     *
     * @since    1.0.0
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Settings table
        $table_settings = $wpdb->prefix . 'ai_assistant_settings';
        $sql_settings = "CREATE TABLE $table_settings (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            setting_key varchar(100) NOT NULL,
            setting_value longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY setting_key (setting_key)
        ) $charset_collate;";
        
        // Logs table
        $table_logs = $wpdb->prefix . 'ai_assistant_logs';
        $sql_logs = "CREATE TABLE $table_logs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            action varchar(50) NOT NULL,
            post_id bigint(20) DEFAULT NULL,
            user_id bigint(20) DEFAULT NULL,
            status varchar(20) NOT NULL,
            message text,
            metadata longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Media meta table
        $table_media_meta = $wpdb->prefix . 'ai_assistant_media_meta';
        $sql_media_meta = "CREATE TABLE $table_media_meta (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            attachment_id bigint(20) NOT NULL,
            ai_description text,
            ai_alt_text text,
            confidence_score decimal(3,2),
            processed_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY attachment_id (attachment_id)
        ) $charset_collate;";

        // Usage tracking table
        $table_usage = $wpdb->prefix . 'ai_assistant_usage';
        $sql_usage = "CREATE TABLE $table_usage (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            provider varchar(50) NOT NULL,
            action varchar(100) NOT NULL,
            model varchar(100) NOT NULL,
            input_tokens int(11) DEFAULT 0,
            output_tokens int(11) DEFAULT 0,
            total_tokens int(11) DEFAULT 0,
            cost decimal(10,6) DEFAULT 0.000000,
            user_id bigint(20) DEFAULT NULL,
            post_id bigint(20) DEFAULT NULL,
            metadata longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY provider (provider),
            KEY action (action),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Chat logs table
        $table_chat_logs = $wpdb->prefix . 'ai_assistant_chat_logs';
        $sql_chat_logs = "CREATE TABLE $table_chat_logs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) DEFAULT NULL,
            session_id varchar(100) NOT NULL,
            user_message text NOT NULL,
            ai_response text NOT NULL,
            context varchar(50) DEFAULT 'general',
            tokens_used int(11) DEFAULT 0,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY session_id (session_id),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_settings);
        dbDelta($sql_logs);
        dbDelta($sql_media_meta);
        dbDelta($sql_usage);
        dbDelta($sql_chat_logs);
    }
    
    /**
     * Set default plugin options
     *
     * @since    1.0.0
     */
    private static function set_default_options() {
        $default_options = array(
            'active_provider' => 'openai',
            'enabled_post_types' => array('post', 'page'),
            'auto_generate_alt_text' => true,
            'auto_tagging_enabled' => false,
            'auto_generate_excerpt' => false,
            'auto_categorize_enabled' => false,
            'auto_tag_media_enabled' => true,
            'ai_credentials' => array(),
            'openai_model' => 'gpt-4o-mini',
            'anthropic_model' => 'claude-3-haiku-20240307',
            'google_model' => 'gemini-pro',
            // Chat settings
            'chat_enabled' => false,
            'chat_enabled_in_admin' => true,
            'chat_enabled_for_all' => false,
            'chat_enabled_for_visitors' => false,
            'chat_show_in_admin_bar' => true,
            'chat_load_on_pages' => 'all',
            'chat_specific_pages' => array(),
            'chat_max_tokens' => 1000,
            'chat_temperature' => 0.7,
            'chat_welcome_message' => __('שלום! אני העוזר הדיגיטלי של האתר. איך אוכל לעזור לך היום?', 'wordpress-ai-assistant'),
            'chat_auto_open' => false,
            'chat_sound_enabled' => true,
            'chat_show_timestamp' => true,
            'chat_enable_markdown' => true,
            'chat_auto_show_floating' => true, // Show floating chat by default
        );

        foreach ($default_options as $key => $value) {
            if (get_option('at_ai_assistant_' . $key) === false) {
                add_option('at_ai_assistant_' . $key, $value);
            }
        }
    }
}