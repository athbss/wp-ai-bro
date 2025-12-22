<?php
/**
 * בודק תלויות התוסף ומציע פתרונות להתקנה
 * 
 * מחלקה זו אחראית על:
 * - בדיקת קיום ספריות חיצוניות (Feature API, וכו')
 * - הצגת התראות למנהל
 * - אפשרות להתקנה/הפעלה של רכיבים חסרים
 *
 * @since      1.2.1
 * @package    WordPress_AI_Assistant
 * @subpackage WordPress_AI_Assistant/includes
 * @author     Amit Trabelsi <amit@amit-trabelsi.co.il>
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * מחלקת בודק תלויות
 */
class AT_Dependency_Checker {

    /**
     * רשימת תלויות נדרשות/מומלצות
     * 
     * @var array
     */
    private $dependencies = array();

    /**
     * תלויות חסרות
     * 
     * @var array
     */
    private $missing_dependencies = array();

    /**
     * האם בדיקה כבר בוצעה
     * 
     * @var bool
     */
    private $checked = false;

    /**
     * Instance יחיד
     * 
     * @var AT_Dependency_Checker
     */
    private static $instance = null;

    /**
     * קבלת instance יחיד
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->define_dependencies();
        $this->init_hooks();
    }

    /**
     * הגדרת תלויות נדרשות
     */
    private function define_dependencies() {
        /**
         * הגדרת תלויות:
         * - type: plugin|library|extension
         * - status: required|recommended|optional
         * - check_method: פונקציה לבדיקת קיום
         * - install_method: אופן התקנה (plugin|composer|manual)
         */
        
        // WordPress Abilities API - חלק מיוזמת AI Building Blocks
        $this->dependencies['wp_abilities_api'] = array(
            'name' => 'WordPress Abilities API',
            'type' => 'core', // מתוכנן להיות מוטמע ב-WP 6.9
            'status' => 'optional',
            'description' => __('API רשמי של WordPress לגילוי והצהרה על יכולות תוספים ונושאים בצורה machine-readable. חלק מיוזמת "AI Building Blocks for WordPress". ייתכן שמוטמע ב-WordPress 6.9+', 'wordpress-ai-assistant'),
            'check_method' => array($this, 'check_wp_abilities_api'),
            'install_method' => 'composer', // ניתן להתקין דרך: composer require wordpress/abilities-api
            'docs_url' => 'https://github.com/WordPress/abilities-api',
        );

        // בדיקת PHP Extensions נדרשות
        $this->dependencies['php_curl'] = array(
            'name' => 'PHP cURL',
            'type' => 'extension',
            'status' => 'required',
            'description' => __('נדרש לתקשורת עם API של ספקי AI.', 'wordpress-ai-assistant'),
            'check_method' => array($this, 'check_php_curl'),
            'install_method' => 'system',
            'docs_url' => 'https://www.php.net/manual/en/curl.installation.php',
        );

        $this->dependencies['php_json'] = array(
            'name' => 'PHP JSON',
            'type' => 'extension',
            'status' => 'required',
            'description' => __('נדרש לעיבוד נתונים מ-API.', 'wordpress-ai-assistant'),
            'check_method' => array($this, 'check_php_json'),
            'install_method' => 'system',
            'docs_url' => 'https://www.php.net/manual/en/json.installation.php',
        );

        // AT Agency Sites Manager integration
        $this->dependencies['at_agency_manager'] = array(
            'name' => 'AT Agency Sites Manager',
            'type' => 'plugin',
            'status' => 'recommended',
            'description' => __('תוסף הניהול המרכזי של הסוכנות. מאפשר ניטור, גיבויים וניהול גרסאות.', 'wordpress-ai-assistant'),
            'check_method' => array($this, 'check_at_agency_manager'),
            'install_method' => 'plugin',
            'download_url' => 'https://github.com/amit-trabelsi-digital/at-agency-sites-manager-wp-plugin/archive/refs/heads/main.zip',
            'slug' => 'at-agency-sites-manager-wp-plugin-main', // GitHub zip folder name usually
            'plugin_file' => 'at-agency-sites-manager-plugin/at-agency-sites-manager.php',
        );

        // אפשר להוסיף עוד תלויות כאן בעתיד
    }

    /**
     * אתחול hooks
     */
    private function init_hooks() {
        // הצגת התראות בממשק הניהול
        add_action('admin_notices', array($this, 'display_dependency_notices'));
        
        // AJAX handlers
        add_action('wp_ajax_at_ai_install_dependency', array($this, 'ajax_install_dependency'));
        add_action('wp_ajax_at_ai_dismiss_dependency_notice', array($this, 'ajax_dismiss_notice'));
    }

    /**
     * בדיקת כל התלויות
     * 
     * @return array מערך של תלויות חסרות
     */
    public function check_all_dependencies() {
        if ($this->checked) {
            return $this->missing_dependencies;
        }

        $this->missing_dependencies = array();

        foreach ($this->dependencies as $key => $dependency) {
            if (!call_user_func($dependency['check_method'])) {
                $this->missing_dependencies[$key] = $dependency;
            }
        }

        $this->checked = true;
        return $this->missing_dependencies;
    }

    /**
     * קבלת תלויות חסרות לפי סוג
     * 
     * @param string $status required|recommended|optional
     * @return array
     */
    public function get_missing_by_status($status) {
        $this->check_all_dependencies();
        
        return array_filter($this->missing_dependencies, function($dep) use ($status) {
            return $dep['status'] === $status;
        });
    }

    /**
     * בדיקה האם יש תלויות חסרות קריטיות
     * 
     * @return bool
     */
    public function has_critical_missing() {
        $required = $this->get_missing_by_status('required');
        return !empty($required);
    }

    /**
     * הצגת התראות על תלויות חסרות
     */
    public function display_dependency_notices() {
        // בדיקה רק בעמודי הניהול הרלוונטיים
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'wordpress-ai-assistant') === false) {
            return;
        }

        // בדיקה אם המשתמש יכול לנהל תוספים
        if (!current_user_can('manage_options')) {
            return;
        }

        // בדיקת תלויות נדרשות (קריטיות)
        $required_missing = $this->get_missing_by_status('required');
        if (!empty($required_missing)) {
            $this->render_notice($required_missing, 'error', 'required');
        }

        // בדיקת תלויות מומלצות
        $recommended_missing = $this->get_missing_by_status('recommended');
        if (!empty($recommended_missing)) {
            // בדיקה אם המשתמש ביטל את ההתראה
            $dismissed = get_user_meta(get_current_user_id(), 'at_ai_dismissed_recommended_deps', true);
            if (!$dismissed) {
                $this->render_notice($recommended_missing, 'warning', 'recommended');
            }
        }
    }

    /**
     * רינדור התראה
     * 
     * @param array  $dependencies תלויות חסרות
     * @param string $type סוג התראה (error|warning|info)
     * @param string $status סטטוס תלות (required|recommended)
     */
    private function render_notice($dependencies, $type, $status) {
        $notice_id = 'at-ai-dependency-notice-' . $status;
        $is_dismissible = ($status !== 'required');
        
        ?>
        <div class="notice notice-<?php echo esc_attr($type); ?> <?php echo $is_dismissible ? 'is-dismissible' : ''; ?>" 
             id="<?php echo esc_attr($notice_id); ?>" 
             style="position: relative;">
            
            <div style="display: flex; align-items: start; gap: 15px; padding: 5px 0;">
                <div style="flex-shrink: 0; font-size: 24px; margin-top: 5px;">
                    <?php echo $status === 'required' ? '⚠️' : '💡'; ?>
                </div>
                
                <div style="flex-grow: 1;">
                    <h3 style="margin: 0 0 10px 0;">
                        <?php
                        if ($status === 'required') {
                            _e('תלויות נדרשות חסרות - WordPress AI Assistant', 'wordpress-ai-assistant');
                        } else {
                            _e('תלויות מומלצות - שפר את יכולות ה-AI Assistant', 'wordpress-ai-assistant');
                        }
                        ?>
                    </h3>
                    
                    <p style="margin: 0 0 15px 0;">
                        <?php
                        if ($status === 'required') {
                            _e('התוסף זקוק לרכיבים הבאים כדי לפעול כראוי:', 'wordpress-ai-assistant');
                        } else {
                            _e('התקנת הרכיבים הבאים תשפר את הביצועים והיכולות של התוסף:', 'wordpress-ai-assistant');
                        }
                        ?>
                    </p>

                    <div class="at-ai-missing-deps" style="margin-bottom: 15px;">
                        <?php foreach ($dependencies as $key => $dep): ?>
                            <div class="at-ai-dep-item" 
                                 style="background: <?php echo $status === 'required' ? '#fff3cd' : '#d1ecf1'; ?>; 
                                        padding: 12px 15px; 
                                        border-right: 3px solid <?php echo $status === 'required' ? '#ff9800' : '#17a2b8'; ?>; 
                                        margin-bottom: 10px;
                                        border-radius: 3px;">
                                
                                <div style="display: flex; justify-content: space-between; align-items: start; gap: 15px;">
                                    <div style="flex-grow: 1;">
                                        <strong style="font-size: 14px;"><?php echo esc_html($dep['name']); ?></strong>
                                        
                                        <?php if ($dep['status'] === 'required'): ?>
                                            <span style="color: #d9534f; font-size: 11px; font-weight: bold; margin-right: 8px;">
                                                (<?php _e('נדרש', 'wordpress-ai-assistant'); ?>)
                                            </span>
                                        <?php endif; ?>
                                        
                                        <p style="margin: 5px 0 0 0; color: #666; font-size: 13px;">
                                            <?php echo esc_html($dep['description']); ?>
                                        </p>
                                    </div>
                                    
                                    <div style="flex-shrink: 0;">
                                        <?php if ($dep['install_method'] === 'manual' && !empty($dep['docs_url'])): ?>
                                            <a href="<?php echo esc_url($dep['docs_url']); ?>" 
                                               class="button button-secondary button-small" 
                                               target="_blank" 
                                               rel="noopener noreferrer">
                                                <?php _e('הוראות התקנה', 'wordpress-ai-assistant'); ?>
                                            </a>
                                        <?php elseif ($dep['install_method'] === 'system' && !empty($dep['docs_url'])): ?>
                                            <a href="<?php echo esc_url($dep['docs_url']); ?>" 
                                               class="button button-secondary button-small" 
                                               target="_blank" 
                                               rel="noopener noreferrer">
                                                <?php _e('מידע טכני', 'wordpress-ai-assistant'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($status !== 'required'): ?>
                        <p style="margin: 0; font-size: 12px; color: #666;">
                            <em><?php _e('ההתראה הזו תוצג רק בעמודי הגדרות ה-AI Assistant. ניתן להתעלם בלחיצה על ה-X.', 'wordpress-ai-assistant'); ?></em>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($is_dismissible): ?>
                <script type="text/javascript">
                jQuery(document).ready(function($) {
                    $('#<?php echo esc_js($notice_id); ?>').on('click', '.notice-dismiss', function() {
                        $.post(ajaxurl, {
                            action: 'at_ai_dismiss_dependency_notice',
                            nonce: '<?php echo wp_create_nonce('at_ai_dismiss_notice'); ?>',
                            status: '<?php echo esc_js($status); ?>'
                        });
                    });
                });
                </script>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * AJAX handler לביטול התראה
     */
    public function ajax_dismiss_notice() {
        check_ajax_referer('at_ai_dismiss_notice', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('אין הרשאה', 'wordpress-ai-assistant'));
        }

        $status = sanitize_text_field($_POST['status'] ?? '');
        
        if ($status === 'recommended') {
            update_user_meta(get_current_user_id(), 'at_ai_dismissed_recommended_deps', true);
        }

        wp_send_json_success();
    }

    // ============================================
    // פונקציות בדיקה ספציפיות
    // ============================================

    /**
     * בדיקת WordPress Abilities API
     * 
     * Abilities API הוא חלק רשמי מיוזמת "AI Building Blocks for WordPress".
     * מאפשר לתוספים ולthemes להצהיר על יכולות שלהם בצורה machine-readable.
     * 
     * @see https://github.com/WordPress/abilities-api
     * @see https://make.wordpress.org/ai/2025/07/17/abilities-api/
     * @return bool
     */
    private function check_wp_abilities_api() {
        global $wp_version;
        
        // בדיקה 1: האם Abilities API מוטמע ב-WordPress Core (6.9+)
        // כרגע בסטטוס "in progress" ל-6.9
        if (version_compare($wp_version, '6.9', '>=')) {
            // וודא שהפונקציות באמת קיימות (במקרה של גרסת RC)
            if (function_exists('wp_register_ability') || function_exists('wp_get_ability')) {
                return true;
            }
        }

        // בדיקה 2: האם התוסף Feature Plugin מותקן ופעיל
        // שם התוסף: "Abilities API" או "abilities-api"
        if (function_exists('is_plugin_active')) {
            if (is_plugin_active('abilities-api/abilities-api.php') || 
                is_plugin_active('abilities-api/plugin.php')) {
                return true;
            }
        }

        // בדיקה 3: האם חבילת Composer מותקנת
        // החבילה: wordpress/abilities-api
        if (class_exists('WordPress\\AbilitiesAPI\\Registry') || 
            class_exists('WP_Abilities_Registry') ||
            class_exists('WordPress\\Abilities\\API')) {
            return true;
        }

        // בדיקה 4: פונקציות עיקריות של ה-API
        if (function_exists('wp_register_ability') || 
            function_exists('wp_get_ability') ||
            function_exists('wp_abilities_api') ||
            function_exists('wp_abilities')) {
            return true;
        }

        return false;
    }

    /**
     * בדיקת PHP cURL
     * 
     * @return bool
     */
    private function check_php_curl() {
        return function_exists('curl_version');
    }

    /**
     * בדיקת PHP JSON
     * 
     * @return bool
     */
    private function check_php_json() {
        return function_exists('json_encode') && function_exists('json_decode');
    }

    /**
     * בדיקת AT Agency Sites Manager
     * 
     * @return bool
     */
    private function check_at_agency_manager() {
        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        
        // Check standard paths
        return is_plugin_active('at-agency-sites-manager-plugin/at-agency-sites-manager.php') ||
               defined('AT_AGENCY_MANAGER_VERSION');
    }

    /**
     * AJAX Handler להתקנת תלות
     */
    public function ajax_install_dependency() {
        check_ajax_referer('at_ai_install_dependency', 'nonce');

        if (!current_user_can('install_plugins')) {
            wp_send_json_error(__('אין הרשאה להתקנת תוספים', 'wordpress-ai-assistant'));
        }

        $dependency_key = sanitize_text_field($_POST['dependency'] ?? '');
        
        if (empty($dependency_key) || !isset($this->dependencies[$dependency_key])) {
            wp_send_json_error(__('תלות לא נמצאה', 'wordpress-ai-assistant'));
        }

        $dependency = $this->dependencies[$dependency_key];

        if ($dependency['install_method'] !== 'plugin' || empty($dependency['download_url'])) {
            wp_send_json_error(__('שיטת התקנה לא נתמכה', 'wordpress-ai-assistant'));
        }

        include_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
        include_once(ABSPATH . 'wp-admin/includes/plugin-install.php');

        $upgrader = new Plugin_Upgrader(new WP_Ajax_Upgrader_Skin());
        
        $result = $upgrader->install($dependency['download_url']);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        if (!$result) {
            wp_send_json_error(__('ההתקנה נכשלה', 'wordpress-ai-assistant'));
        }

        // Try to activate
        if (!empty($dependency['plugin_file'])) {
            $activate = activate_plugin($dependency['plugin_file']);
            
            if (is_wp_error($activate)) {
                // Try to find the file if path is different (e.g. GitHub folder name)
                // This is tricky with zip installs as folder name might vary
                wp_send_json_error(__('ההתקנה הצליחה אך ההפעלה נכשלה: ' . $activate->get_error_message(), 'wordpress-ai-assistant'));
            }
        }

        wp_send_json_success(__('הותקן והופעל בהצלחה', 'wordpress-ai-assistant'));
    }

    /**
     * קבלת מידע מלא על סטטוס תלויות
     * לשימוש בעמוד הגדרות או dashboard
     * 
     * @return array
     */
    public function get_dependencies_status() {
        $status = array();
        
        foreach ($this->dependencies as $key => $dependency) {
            $is_available = call_user_func($dependency['check_method']);
            
            $status[$key] = array(
                'name' => $dependency['name'],
                'type' => $dependency['type'],
                'status' => $dependency['status'],
                'description' => $dependency['description'],
                'available' => $is_available,
                'install_method' => $dependency['install_method'],
                'docs_url' => $dependency['docs_url'] ?? '',
            );
        }
        
        return $status;
    }

    /**
     * בדיקה מהירה - האם התוסף יכול לפעול
     * 
     * @return bool|WP_Error
     */
    public function can_plugin_work() {
        $required_missing = $this->get_missing_by_status('required');
        
        if (!empty($required_missing)) {
            $missing_names = array_map(function($dep) {
                return $dep['name'];
            }, $required_missing);
            
            return new WP_Error(
                'missing_dependencies',
                sprintf(
                    __('חסרות תלויות נדרשות: %s', 'wordpress-ai-assistant'),
                    implode(', ', $missing_names)
                )
            );
        }
        
        return true;
    }
}

