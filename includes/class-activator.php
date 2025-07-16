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
            confidence_score decimal(3,2),
            processed_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY attachment_id (attachment_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_settings);
        dbDelta($sql_logs);
        dbDelta($sql_media_meta);
    }
    
    /**
     * Set default plugin options
     *
     * @since    1.0.0
     */
    private static function set_default_options() {
        $default_options = array(
            'ai_provider' => 'openai',
            'enabled_post_types' => array('post', 'page'),
            'auto_excerpt' => true,
            'auto_tagging' => false,
            'tts_enabled' => true,
            'image_analysis_enabled' => true
        );
        
        foreach ($default_options as $key => $value) {
            if (get_option('at_ai_assistant_' . $key) === false) {
                add_option('at_ai_assistant_' . $key, $value);
            }
        }
    }
}