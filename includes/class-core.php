<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @since      1.0.0
 *
 * @package    WordPress_AI_Assistant
 * @subpackage WordPress_AI_Assistant/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    WordPress_AI_Assistant
 * @subpackage WordPress_AI_Assistant/includes
 * @author     Amit Trabelsi <amit@amit-trabelsi.co.il>
 */
class AT_WordPress_AI_Assistant_Core {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      AT_WordPress_AI_Assistant_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        if (defined('WORDPRESS_AI_ASSISTANT_VERSION')) {
            $this->version = WORDPRESS_AI_ASSISTANT_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'wordpress-ai-assistant';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - AT_WordPress_AI_Assistant_Loader. Orchestrates the hooks of the plugin.
     * - AT_WordPress_AI_Assistant_i18n. Defines internationalization functionality.
     * - AT_WordPress_AI_Assistant_Admin. Defines all hooks for the admin area.
     * - AT_WordPress_AI_Assistant_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once WORDPRESS_AI_ASSISTANT_PATH . 'includes/class-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once WORDPRESS_AI_ASSISTANT_PATH . 'includes/class-i18n.php';

        /**
         * Helper functions - Load early as they're used by other classes
         */
        require_once WORDPRESS_AI_ASSISTANT_PATH . 'includes/functions.php';

        /**
         * Suite Adapter - Bridge to AT Agency Manager Suite Core
         */
        require_once WORDPRESS_AI_ASSISTANT_PATH . 'includes/suite-adapter.php';

        /**
         * Dependency Checker - Check for required and recommended dependencies
         */
        require_once WORDPRESS_AI_ASSISTANT_PATH . 'includes/class-dependency-checker.php';

        /**
         * AI Services Infrastructure
         */
        require_once WORDPRESS_AI_ASSISTANT_PATH . 'includes/ai/class-ai-provider.php';
        require_once WORDPRESS_AI_ASSISTANT_PATH . 'includes/ai/class-ai-manager.php';
        require_once WORDPRESS_AI_ASSISTANT_PATH . 'includes/ai/class-ai-usage-tracker.php';
        require_once WORDPRESS_AI_ASSISTANT_PATH . 'includes/ai/class-openai-provider.php';
        require_once WORDPRESS_AI_ASSISTANT_PATH . 'includes/ai/class-anthropic-provider.php';
        require_once WORDPRESS_AI_ASSISTANT_PATH . 'includes/ai/class-google-provider.php';

        /**
         * AI Features
         */
        require_once WORDPRESS_AI_ASSISTANT_PATH . 'includes/features/class-image-alt-generator.php';
        require_once WORDPRESS_AI_ASSISTANT_PATH . 'includes/features/class-text-translator.php';
        require_once WORDPRESS_AI_ASSISTANT_PATH . 'includes/features/class-auto-tagger.php';
        require_once WORDPRESS_AI_ASSISTANT_PATH . 'includes/features/class-media-ai-generator.php';
        require_once WORDPRESS_AI_ASSISTANT_PATH . 'includes/features/class-content-optimizer.php';

        /**
         * Initialize AI Features
         */
        $this->image_alt_generator = new AT_Image_Alt_Generator();
        $this->text_translator = new AT_Text_Translator();
        $this->auto_tagger = new AT_Auto_Tagger();
        $this->media_ai_generator = new AT_Media_AI_Generator();
        $this->content_optimizer = new AT_Content_Optimizer();
        
        // Initialize Chat feature - will check if enabled internally
        require_once WORDPRESS_AI_ASSISTANT_PATH . 'includes/features/class-ai-chat.php';
        
        // Load simple chat immediately for testing
        require_once WORDPRESS_AI_ASSISTANT_PATH . 'includes/features/class-ai-chat-simple.php';
        
        // Initialize full chat on init hook to ensure settings are loaded
        add_action('init', function() {
            if (at_ai_assistant_get_option('chat_enabled', false)) {
                new AT_AI_Chat();
                // Also initialize simple chat for immediate display
                new AT_AI_Chat_Simple();
            }
        });

        /**
         * Initialize Dependency Checker
         */
        $this->dependency_checker = AT_Dependency_Checker::get_instance();

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once WORDPRESS_AI_ASSISTANT_PATH . 'admin/class-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once WORDPRESS_AI_ASSISTANT_PATH . 'public/class-public.php';

        $this->loader = new AT_WordPress_AI_Assistant_Loader();

        // Register AI Manager as a suite service if Suite Core is available
        add_action('at_suite_core_booted', function() {
            if (function_exists('at_suite_register_service')) {
                at_suite_register_service('ai_manager', AT_AI_Manager::get_instance(), array(
                    'owner'        => 'wordpress-ai-assistant',
                    'version'      => $this->get_version(),
                    'capabilities' => array('text_generation', 'chat', 'image_analysis', 'translation'),
                ));
            }
        });
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the AT_WordPress_AI_Assistant_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new AT_WordPress_AI_Assistant_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new AT_WordPress_AI_Assistant_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');
        $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');
        
        // AJAX hooks for admin
        $this->loader->add_action('wp_ajax_at_ai_assistant_test_api', $plugin_admin, 'test_api_connection');
        $this->loader->add_action('wp_ajax_at_ai_assistant_generate_summary', $plugin_admin, 'generate_summary');
        $this->loader->add_action('wp_ajax_at_ai_assistant_generate_tags', $plugin_admin, 'generate_tags');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new AT_WordPress_AI_Assistant_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        
        // Content processing hooks
        $this->loader->add_action('save_post', $plugin_public, 'process_post_save', 10, 2);
        $this->loader->add_action('add_attachment', $plugin_public, 'process_media_upload');
        
        // TTS hooks
        $this->loader->add_action('wp_footer', $plugin_public, 'add_tts_player');
        $this->loader->add_action('wp_ajax_at_ai_assistant_get_tts', $plugin_public, 'get_tts_audio');
        $this->loader->add_action('wp_ajax_nopriv_at_ai_assistant_get_tts', $plugin_public, 'get_tts_audio');
        
        // REST API hooks
        $this->loader->add_action('rest_api_init', $plugin_public, 'register_rest_routes');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    AT_WordPress_AI_Assistant_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}