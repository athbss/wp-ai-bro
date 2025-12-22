<?php
/**
 * AI Usage Tracker
 *
 * @since      1.1.0
 * @package    WordPress_AI_Assistant
 * @subpackage WordPress_AI_Assistant/includes/ai
 * @author     Amit Trabelsi <amit@amit-trabelsi.co.il>
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI Usage Tracker Class
 *
 * Tracks AI API usage and costs
 */
class AT_AI_Usage_Tracker {

    /**
     * Usage table name
     *
     * @var string
     */
    private $table_name;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ai_assistant_usage';

        $this->init_table();
    }

    /**
     * Initialize usage table
     */
    private function init_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_name} (
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

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Track usage
     *
     * @param string $provider
     * @param string $action
     * @param array $usage_data
     * @return bool
     */
    public function track_usage($provider, $action, $usage_data) {
        global $wpdb;

        $model = isset($usage_data['model']) ? $usage_data['model'] : '';
        $usage = isset($usage_data['usage']) ? $usage_data['usage'] : array();
        $input_tokens = isset($usage['input_tokens']) ? $usage['input_tokens'] : 0;
        $output_tokens = isset($usage['output_tokens']) ? $usage['output_tokens'] : 0;
        $total_tokens = isset($usage['total_tokens']) ? $usage['total_tokens'] : ($input_tokens + $output_tokens);

        // Calculate cost
        $ai_manager = AT_AI_Manager::get_instance();
        $provider_instance = $ai_manager->get_provider($provider);

        $cost = 0;
        if ($provider_instance) {
            $usage_with_model = array_merge($usage, array('model' => $model));
            $cost = $provider_instance->calculate_cost($usage_with_model);
        }

        $result = $wpdb->insert(
            $this->table_name,
            array(
                'provider' => $provider,
                'action' => $action,
                'model' => $model,
                'input_tokens' => $input_tokens,
                'output_tokens' => $output_tokens,
                'total_tokens' => $total_tokens,
                'cost' => $cost,
                'user_id' => get_current_user_id(),
                'post_id' => isset($usage_data['post_id']) ? $usage_data['post_id'] : null,
                'metadata' => maybe_serialize(isset($usage_data['metadata']) ? $usage_data['metadata'] : array()),
                'created_at' => current_time('mysql'),
            ),
            array('%s', '%s', '%s', '%d', '%d', '%d', '%f', '%d', '%d', '%s', '%s')
        );

        return $result !== false;
    }

    /**
     * Get usage statistics
     *
     * @param string $period (day, week, month, year)
     * @param array $filters
     * @return array
     */
    public function get_stats($period = 'month', $filters = array()) {
        global $wpdb;

        $date_format = $this->get_date_format($period);
        $where_clause = $this->build_where_clause($filters);

        $query = $wpdb->prepare(
            "SELECT
                DATE_FORMAT(created_at, %s) as period,
                provider,
                COUNT(*) as requests,
                SUM(input_tokens) as total_input_tokens,
                SUM(output_tokens) as total_output_tokens,
                SUM(total_tokens) as total_tokens,
                SUM(cost) as total_cost,
                AVG(cost) as avg_cost_per_request
            FROM {$this->table_name}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 {$period})
            {$where_clause}
            GROUP BY DATE_FORMAT(created_at, %s), provider
            ORDER BY period DESC, provider",
            $date_format,
            $date_format
        );

        $results = $wpdb->get_results($query, ARRAY_A);

        $stats = array(
            'period' => $period,
            'total_requests' => 0,
            'total_tokens' => 0,
            'total_cost' => 0,
            'by_provider' => array(),
            'by_period' => array(),
        );

        foreach ($results as $row) {
            $stats['total_requests'] += intval($row['requests']);
            $stats['total_tokens'] += intval($row['total_tokens']);
            $stats['total_cost'] += floatval($row['total_cost']);

            $provider = $row['provider'];
            $period_key = $row['period'];

            if (!isset($stats['by_provider'][$provider])) {
                $stats['by_provider'][$provider] = array(
                    'requests' => 0,
                    'tokens' => 0,
                    'cost' => 0,
                );
            }

            $stats['by_provider'][$provider]['requests'] += intval($row['requests']);
            $stats['by_provider'][$provider]['tokens'] += intval($row['total_tokens']);
            $stats['by_provider'][$provider]['cost'] += floatval($row['total_cost']);

            if (!isset($stats['by_period'][$period_key])) {
                $stats['by_period'][$period_key] = array();
            }

            $stats['by_period'][$period_key][$provider] = array(
                'requests' => intval($row['requests']),
                'tokens' => intval($row['total_tokens']),
                'cost' => floatval($row['total_cost']),
            );
        }

        return $stats;
    }

    /**
     * Get date format for period
     *
     * @param string $period
     * @return string
     */
    private function get_date_format($period) {
        $formats = array(
            'day' => '%Y-%m-%d %H:00',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            'year' => '%Y',
        );

        return isset($formats[$period]) ? $formats[$period] : $formats['month'];
    }

    /**
     * Build WHERE clause for filters
     *
     * @param array $filters
     * @return string
     */
    private function build_where_clause($filters) {
        $where_parts = array();

        if (!empty($filters['provider'])) {
            $where_parts[] = $GLOBALS['wpdb']->prepare("provider = %s", $filters['provider']);
        }

        if (!empty($filters['user_id'])) {
            $where_parts[] = $GLOBALS['wpdb']->prepare("user_id = %d", $filters['user_id']);
        }

        if (!empty($filters['action'])) {
            $where_parts[] = $GLOBALS['wpdb']->prepare("action = %s", $filters['action']);
        }

        if (!empty($where_parts)) {
            return "AND " . implode(" AND ", $where_parts);
        }

        return "";
    }

    /**
     * Get recent usage history
     *
     * @param int $limit
     * @param array $filters
     * @return array
     */
    public function get_recent_usage($limit = 50, $filters = array()) {
        global $wpdb;

        $where_clause = $this->build_where_clause($filters);

        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name}
            WHERE 1=1 {$where_clause}
            ORDER BY created_at DESC
            LIMIT %d",
            $limit
        );

        $results = $wpdb->get_results($query, ARRAY_A);

        // Add user and post information
        foreach ($results as &$result) {
            if (!empty($result['user_id'])) {
                $user = get_userdata($result['user_id']);
                $result['user_name'] = $user ? $user->display_name : __('Unknown User', 'wordpress-ai-assistant');
            }

            if (!empty($result['post_id'])) {
                $post = get_post($result['post_id']);
                $result['post_title'] = $post ? $post->post_title : __('Unknown Post', 'wordpress-ai-assistant');
            }

            $result['metadata'] = maybe_unserialize($result['metadata']);
            $result['cost'] = floatval($result['cost']);
        }

        return $results;
    }

    /**
     * Clean up old usage data
     *
     * @param int $days_old
     * @return int Number of deleted records
     */
    public function cleanup_old_data($days_old = 365) {
        global $wpdb;

        $result = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_name}
            WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days_old
        ));

        return intval($result);
    }

    /**
     * Get cost summary by provider
     *
     * @param string $period
     * @return array
     */
    public function get_cost_summary($period = 'month') {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT
                provider,
                SUM(cost) as total_cost,
                SUM(total_tokens) as total_tokens,
                COUNT(*) as total_requests,
                AVG(cost) as avg_cost_per_request
            FROM {$this->table_name}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 {$period})
            GROUP BY provider
            ORDER BY total_cost DESC",
            $period
        );

        $results = $wpdb->get_results($query, ARRAY_A);

        $summary = array(
            'providers' => array(),
            'total_cost' => 0,
            'total_tokens' => 0,
            'total_requests' => 0,
        );

        foreach ($results as $row) {
            $provider = $row['provider'];
            $summary['providers'][$provider] = array(
                'cost' => floatval($row['total_cost']),
                'tokens' => intval($row['total_tokens']),
                'requests' => intval($row['total_requests']),
                'avg_cost_per_request' => floatval($row['avg_cost_per_request']),
            );

            $summary['total_cost'] += floatval($row['total_cost']);
            $summary['total_tokens'] += intval($row['total_tokens']);
            $summary['total_requests'] += intval($row['total_requests']);
        }

        return $summary;
    }
}
