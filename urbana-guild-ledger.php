<?php
/**
 * Plugin Name: Urbana Guild Ledger
 * Plugin URI: https://urbana.com
 * Description: A secure and efficient way for the Urbana team to log and track personal interactions with key contacts (local councils, landscape architects, etc.).
 * Version: 1.0.1
 * Author: Urbana Team
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: urbana-guild-ledger
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Urbana Guild Ledger Class
 * 
 * Changelog:
 * Version 1.0.1 (2025-10-08) - Beta Update:
 * - Implemented modern DataViews-style interface for listing page
 * - Added real-time search filtering without page reload
 * - Added CSV export functionality (beta)
 * - Added color-coded interaction type badges
 * - Enhanced responsive design for mobile devices
 * - Added smooth animations and transitions
 * - Added quick action buttons on hover
 * - Enabled REST API support for modern features
 * - Fixed HTTP 500 error during plugin activation
 * 
 * Version 1.0.0 (2025-09-29):
 * - Initial release
 * - Custom post type for ledger entries
 * - Admin menu structure
 * - Data entry form with meta boxes
 * - Basic list table display
 * - Security implementation
 */
class Urbana_Guild_Ledger {
    
    /**
     * Plugin version
     */
    const VERSION = '1.0.1';
    
    /**
     * Custom post type name
     */
    const POST_TYPE = 'urbana_ledger';
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Get instance of this class
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
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_head', array($this, 'fix_menu_highlight'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Enable DataViews support
        add_filter('use_block_editor_for_post_type', array($this, 'enable_dataviews'), 10, 2);
        
        // Configure custom columns for DataViews
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', array($this, 'set_custom_columns'));
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
        add_filter('manage_edit-' . self::POST_TYPE . '_sortable_columns', array($this, 'sortable_columns'));
        add_action('pre_get_posts', array($this, 'custom_orderby'));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        $this->register_post_type();
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Register the custom post type
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => _x('Guild Ledger', 'Post type general name', 'urbana-guild-ledger'),
            'singular_name'         => _x('Ledger Entry', 'Post type singular name', 'urbana-guild-ledger'),
            'menu_name'             => _x('Guild Ledger', 'Admin Menu text', 'urbana-guild-ledger'),
            'name_admin_bar'        => _x('Ledger Entry', 'Add New on Toolbar', 'urbana-guild-ledger'),
            'add_new'               => __('Add New', 'urbana-guild-ledger'),
            'add_new_item'          => __('Add New Ledger Entry', 'urbana-guild-ledger'),
            'new_item'              => __('New Ledger Entry', 'urbana-guild-ledger'),
            'edit_item'             => __('Edit Ledger Entry', 'urbana-guild-ledger'),
            'view_item'             => __('View Ledger Entry', 'urbana-guild-ledger'),
            'all_items'             => __('All Ledger Entries', 'urbana-guild-ledger'),
            'search_items'          => __('Search Ledger Entries', 'urbana-guild-ledger'),
            'parent_item_colon'     => __('Parent Ledger Entries:', 'urbana-guild-ledger'),
            'not_found'             => __('No ledger entries found.', 'urbana-guild-ledger'),
            'not_found_in_trash'    => __('No ledger entries found in Trash.', 'urbana-guild-ledger'),
            'featured_image'        => _x('Ledger Entry Featured Image', 'Overrides the "Featured Image" phrase', 'urbana-guild-ledger'),
            'set_featured_image'    => _x('Set featured image', 'Overrides the "Set featured image" phrase', 'urbana-guild-ledger'),
            'remove_featured_image' => _x('Remove featured image', 'Overrides the "Remove featured image" phrase', 'urbana-guild-ledger'),
            'use_featured_image'    => _x('Use as featured image', 'Overrides the "Use as featured image" phrase', 'urbana-guild-ledger'),
            'archives'              => _x('Ledger Entry archives', 'The post type archive label used in nav menus', 'urbana-guild-ledger'),
            'insert_into_item'      => _x('Insert into ledger entry', 'Overrides the "Insert into post"/"Insert into page" phrase', 'urbana-guild-ledger'),
            'uploaded_to_this_item' => _x('Uploaded to this ledger entry', 'Overrides the "Uploaded to this post"/"Uploaded to this page" phrase', 'urbana-guild-ledger'),
            'filter_items_list'     => _x('Filter ledger entries list', 'Screen reader text for the filter links', 'urbana-guild-ledger'),
            'items_list_navigation' => _x('Ledger entries list navigation', 'Screen reader text for the pagination', 'urbana-guild-ledger'),
            'items_list'            => _x('Ledger entries list', 'Screen reader text for the items list', 'urbana-guild-ledger'),
        );
        
        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => false, // We'll add our own menu
            'show_in_nav_menus'  => false,
            'show_in_admin_bar'  => false,
            'query_var'          => false,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'capabilities'       => array(
                'read'              => 'manage_options',
                'edit_posts'        => 'manage_options',
                'edit_others_posts' => 'manage_options',
                'delete_posts'      => 'manage_options',
                'delete_others_posts' => 'manage_options',
                'read_private_posts' => 'manage_options',
                'edit_post'         => 'manage_options',
                'delete_post'       => 'manage_options',
                'read_post'         => 'manage_options',
                'publish_posts'     => 'manage_options',
            ),
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title', 'custom-fields'),
            'show_in_rest'       => true, // Enable REST API for DataViews support
        );
        
        register_post_type(self::POST_TYPE, $args);
    }
    
    /**
     * Enable DataViews for the post type listing
     */
    public function enable_dataviews($use_block_editor, $post_type) {
        // Keep block editor disabled for editing individual posts
        if ($post_type === self::POST_TYPE) {
            return false;
        }
        return $use_block_editor;
    }

    /**
     * Register custom taxonomies
     */
    public function register_taxonomies() {
        $labels = array(
            'name' => _x('Lead Statuses', 'taxonomy general name', 'urbana-guild-ledger'),
            'singular_name' => _x('Lead Status', 'taxonomy singular name', 'urbana-guild-ledger'),
            'search_items' => __('Search Lead Statuses', 'urbana-guild-ledger'),
            'all_items' => __('All Lead Statuses', 'urbana-guild-ledger'),
            'edit_item' => __('Edit Lead Status', 'urbana-guild-ledger'),
            'update_item' => __('Update Lead Status', 'urbana-guild-ledger'),
            'add_new_item' => __('Add New Lead Status', 'urbana-guild-ledger'),
            'new_item_name' => __('New Lead Status', 'urbana-guild-ledger'),
        );

        register_taxonomy('lead_status', array(self::POST_TYPE), array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'hierarchical' => false,
            'show_admin_column' => false,
        ));
    }

    /**
     * Register REST API routes for AJAX filtering and dashboard data
     */
    public function register_rest_routes() {
        register_rest_route('urbana-guild-ledger/v1', '/entries', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_list_entries'),
            'permission_callback' => function() {
                // Allow administrators and editors (or any role with edit_posts) to fetch entries
                return current_user_can('manage_options') || current_user_can('edit_posts');
            }
        ));

        register_rest_route('urbana-guild-ledger/v1', '/lead-statuses', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_get_lead_statuses'),
            'permission_callback' => function() {
                // Allow administrators and editors (or any role with edit_posts) to fetch lead statuses
                return current_user_can('manage_options') || current_user_can('edit_posts');
            }
        ));

        register_rest_route('urbana-guild-ledger/v1', '/stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_stats'),
            'permission_callback' => function() {
                // Allow administrators and editors (or any role with edit_posts) to fetch stats
                return current_user_can('manage_options') || current_user_can('edit_posts');
            }
        ));
    }

    /**
     * REST: List entries for AJAX table
     */
    public function rest_list_entries($request) {
        try {
            $params = $request->get_params();
            $search = isset($params['s']) ? sanitize_text_field($params['s']) : '';
            $interaction_type = isset($params['interaction_type']) ? sanitize_text_field($params['interaction_type']) : '';
            $lead_status = isset($params['lead_status']) ? sanitize_text_field($params['lead_status']) : '';
            $start_date = isset($params['start_date']) ? sanitize_text_field($params['start_date']) : '';
            $end_date = isset($params['end_date']) ? sanitize_text_field($params['end_date']) : '';
            $per_page = isset($params['per_page']) ? intval($params['per_page']) : 20;
            $page = isset($params['page']) ? intval($params['page']) : 1;

// Build meta_query conditionally only when needed to avoid unnecessary slow queries
        $meta_query = array();

        if ($interaction_type) {
            if (empty($meta_query)) $meta_query['relation'] = 'AND';
            $meta_query[] = array(
                'key' => '_urbana_interaction_type',
                'value' => $interaction_type,
                'compare' => '='
            );
        }

        if ($start_date && $end_date) {
            if (empty($meta_query)) $meta_query['relation'] = 'AND';
            $meta_query[] = array(
                'key' => '_urbana_interaction_date',
                'value' => array($start_date, $end_date),
                'compare' => 'BETWEEN',
                'type' => 'DATE'
            );
        } elseif ($start_date) {
            if (empty($meta_query)) $meta_query['relation'] = 'AND';
            $meta_query[] = array(
                'key' => '_urbana_interaction_date',
                'value' => $start_date,
                'compare' => '>=',
                'type' => 'DATE'
            );
        } elseif ($end_date) {
            if (empty($meta_query)) $meta_query['relation'] = 'AND';
            $meta_query[] = array(
                'key' => '_urbana_interaction_date',
                'value' => $end_date,
                'compare' => '<=',
                'type' => 'DATE'
            );
        }

        // Add search across meta fields
        if ($search) {
            if (empty($meta_query)) $meta_query['relation'] = 'AND';
                $meta_query[] = array(
                    'relation' => 'OR',
                    array(
                        'key' => '_urbana_contact_name',
                        'value' => $search,
                        'compare' => 'LIKE'
                    ),
                    array(
                        'key' => '_urbana_company_council',
                        'value' => $search,
                        'compare' => 'LIKE'
                    ),
                    array(
                        'key' => '_urbana_notes',
                        'value' => $search,
                        'compare' => 'LIKE'
                    ),
                );
            }

            // Build query args; add meta_query only if we have conditions to avoid unnecessary slow queries
            $args = array(
                'post_type' => self::POST_TYPE,
                'posts_per_page' => $per_page,
                'paged' => $page,
            );
            if ( ! empty( $meta_query ) ) {
                // meta_query is only applied when filters/search are active. This can be slow on very large tables
                // because meta is not indexed like core fields; however it is required for accurate filtering across
                // interaction type, dates, and meta-based search fields. If you expect heavy usage, consider
                // promoting frequently-filtered fields (e.g. interaction type) to a taxonomy or adding an index
                // outside of WordPress. Suppress the SlowDBQuery warning with a phpcs ignore because this
                // is conditional, necessary functionality and the results are paged/cached.
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- meta_query is required only when filters/search are active
                $args['meta_query'] = $meta_query;
            }

            if ($lead_status) {
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- tax_query is required for filtering by lead status
                $args['tax_query'] = array(
                    array(
                        'taxonomy' => 'lead_status',
                        'field' => 'slug',
                        'terms' => array($lead_status)
                    )
                );
            }

            $query = new WP_Query($args);

            $items = array();
            foreach ($query->posts as $post) {
                $contact = get_post_meta($post->ID, '_urbana_contact_name', true);
                $company = get_post_meta($post->ID, '_urbana_company_council', true);
                $date = get_post_meta($post->ID, '_urbana_interaction_date', true);
                $type = get_post_meta($post->ID, '_urbana_interaction_type', true);
                $terms = wp_get_post_terms($post->ID, 'lead_status');
                $status = !empty($terms) ? $terms[0]->name : '';

                $items[] = array(
                    'id' => $post->ID,
                    // Decode any HTML entities that may be stored in the title so the client receives the proper characters
                    'title' => html_entity_decode( get_the_title($post), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
                    'edit_url' => get_edit_post_link($post->ID),
                    'contact' => $contact,
                    'company' => $company,
                    'date' => $date ? date_i18n('M j, Y', strtotime($date)) : '',
                    'interaction_type' => $type,
                    'lead_status' => $status,
                );
            }

            return rest_ensure_response(array(
                'items' => $items,
                'total' => $query->found_posts,
                'pages' => (int) ceil($query->found_posts / $per_page),
            ));

        } catch (Exception $e) {
            if ( defined('WP_DEBUG') && WP_DEBUG ) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- only log during WP_DEBUG for debugging
                error_log('Urbana: rest_list_entries exception: ' . $e->getMessage());
            }
            return new WP_Error('internal_server_error', 'Server error while listing entries', array('status' => 500));
        }
    }

    /**
     * REST: Return lead status terms
     */
    public function rest_get_lead_statuses($request) {
        try {
            $terms = get_terms(array('taxonomy' => 'lead_status', 'hide_empty' => false));
            if ( is_wp_error($terms) ) {
                if ( defined('WP_DEBUG') && WP_DEBUG ) {
                    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- only log during WP_DEBUG for debugging
                    error_log('Urbana: get_terms failed: ' . $terms->get_error_message());
                }
                return new WP_Error('lead_status_error', 'Could not load lead statuses', array('status' => 500));
            }
            $out = array();
            foreach ( (array) $terms as $t) {
                $out[] = array('slug' => $t->slug, 'name' => $t->name);
            }
            return rest_ensure_response($out);
        } catch (Exception $e) {
            if ( defined('WP_DEBUG') && WP_DEBUG ) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- only log during WP_DEBUG for debugging
                error_log('Urbana: rest_get_lead_statuses exception: ' . $e->getMessage());
            }
            return new WP_Error('internal_server_error', 'Server error while loading lead statuses', array('status' => 500));
        }
    }

    /**
     * REST: Stats for dashboard (interactions by month, by type, by lead status)
     */
    public function rest_stats($request) {
        global $wpdb;

        // Cache the stats briefly to avoid heavy DB work on every request
        $cache_key = 'urbana_ledger_stats_v1';
        $cached = get_transient( $cache_key );
        if ( $cached !== false ) {
            return rest_ensure_response( $cached );
        }

        // Get counts by interaction type using WP_Query to avoid direct DB queries
        $types = array('email', 'video_call', 'in_person', 'phone_call');
        $by_type = array();
        foreach ($types as $t) {
            // meta_query used here to count posts by interaction type.
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- counting by meta_value requires meta_query; results are cached in the REST output to avoid repeated load.
            $q = new WP_Query(array(
                'post_type' => self::POST_TYPE,
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- counting by meta_value requires meta_query; results are cached in the REST output to avoid repeated load.
                'meta_query' => array(
                    array('key' => '_urbana_interaction_type', 'value' => $t, 'compare' => '='),
                ),
                'posts_per_page' => 1,
                'fields' => 'ids',
                // do not set no_found_rows => true because we rely on found_posts
            ));
            $by_type[$t] = intval( $q->found_posts );
            wp_reset_postdata();
        }

        // Get counts by lead status using term count where possible
        $statuses = get_terms(array('taxonomy' => 'lead_status', 'hide_empty' => false));
        $by_status = array();
        foreach ($statuses as $s) {
            $by_status[$s->name] = isset($s->count) ? intval($s->count) : 0;
        }

        // Interactions per month for last 12 months
        // Use WP_Query per-month instead of a direct DB aggregation to avoid direct SQL in the distributed plugin.
        // We still cache the results (transient) to avoid repeated work on high-traffic dashboards.
        $by_month = array();
        $now = current_time('timestamp');
        // Build counts for each of the previous 12 months (oldest -> newest)
        for ($i = 11; $i >= 0; $i--) {
            $month_start = date_i18n('Y-m-01', strtotime("-{$i} months", $now));
            $month_end = date_i18n('Y-m-t', strtotime("-{$i} months", $now));

            // Monthly date range count requires a meta_query for accurate results.
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- this query is executed per-month for stats and the entire result is transient-cached to avoid repeated heavy queries.
            $q = new WP_Query(array(
                'post_type' => self::POST_TYPE,
                'posts_per_page' => 1,
                'fields' => 'ids',
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- date-range filtering uses meta_query; results are cached in a transient.
                'meta_query' => array(
                    array(
                        'key' => '_urbana_interaction_date',
                        'value' => array($month_start, $month_end),
                        'compare' => 'BETWEEN',
                        'type' => 'DATE',
                    ),
                ),
            ));

            $by_month[] = array('month' => substr($month_start, 0, 7), 'count' => intval($q->found_posts));
            wp_reset_postdata();
        }

        $out = array('by_type'=>$by_type, 'by_status'=>$by_status, 'by_month'=>$by_month);
        // Cache for 5 minutes
        set_transient( $cache_key, $out, 5 * MINUTE_IN_SECONDS );

        return rest_ensure_response($out);
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Add top-level menu
        add_menu_page(
            __('Urbana', 'urbana-guild-ledger'),           // Page title
            __('Urbana', 'urbana-guild-ledger'),           // Menu title
            'manage_options',                               // Capability
            'urbana-main',                                  // Menu slug
            array($this, 'main_menu_page'),                // Callback function
            'dashicons-groups',                             // Icon
            30                                              // Position
        );
        
        // Add sub-menu for Guild Ledger
        add_submenu_page(
            'urbana-main',                                  // Parent slug
            __('Guild Ledger', 'urbana-guild-ledger'),     // Page title
            __('Guild Ledger', 'urbana-guild-ledger'),     // Menu title
            'manage_options',                               // Capability
            'edit.php?post_type=' . self::POST_TYPE,       // Menu slug
            '',                                             // Callback (empty for redirect)
            1                                               // Position
        );
        
        // Add "Add New" submenu
        add_submenu_page(
            'urbana-main',                                  // Parent slug
            __('Add New Entry', 'urbana-guild-ledger'),    // Page title
            __('Add New Entry', 'urbana-guild-ledger'),    // Menu title
            'manage_options',                               // Capability
            'post-new.php?post_type=' . self::POST_TYPE,   // Menu slug
            '',                                             // Callback (empty for redirect)
            2                                               // Position
        );

        // Add Lead Statuses management link
        add_submenu_page(
            'urbana-main',
            __('Lead Statuses', 'urbana-guild-ledger'),
            __('Lead Statuses', 'urbana-guild-ledger'),
            'manage_options',
            'edit-tags.php?taxonomy=lead_status&post_type=' . self::POST_TYPE,
            '',
            3
        );
        
        // Remove the default "Urbana" submenu item created by add_menu_page
        remove_submenu_page('urbana-main', 'urbana-main');
    }
    
    /**
     * Main menu page content
     */
    public function main_menu_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <p><?php esc_html_e('Welcome to the Urbana admin area.', 'urbana-guild-ledger'); ?></p>
            <div class="card">
                <h2><?php esc_html_e('Guild Ledger', 'urbana-guild-ledger'); ?></h2>
                <p><?php esc_html_e('Manage your contact interactions and track communications with key contacts.', 'urbana-guild-ledger'); ?></p>
                <p>
                    <a href="<?php echo esc_url( admin_url('edit.php?post_type=' . self::POST_TYPE) ); ?>" class="button button-primary">
                        <?php esc_html_e('View All Entries', 'urbana-guild-ledger'); ?>
                    </a>
                    <a href="<?php echo esc_url( admin_url('post-new.php?post_type=' . self::POST_TYPE) ); ?>" class="button">
                        <?php esc_html_e('Add New Entry', 'urbana-guild-ledger'); ?>
                    </a>
                </p>
            </div>

            <h2><?php esc_html_e('Dashboard', 'urbana-guild-ledger'); ?></h2>
            <div class="urbana-dashboard-cards">
                <div class="urbana-dashboard-card">
                    <h3><?php esc_html_e('Interactions (last 12 months)', 'urbana-guild-ledger'); ?></h3>
                    <canvas id="chart-interactions-months" width="400" height="160"></canvas>
                </div>

                <div class="urbana-dashboard-card">
                    <h3><?php esc_html_e('Interactions by Type', 'urbana-guild-ledger'); ?></h3>
                    <canvas id="chart-interactions-type" width="400" height="160"></canvas>
                </div>

                <div class="urbana-dashboard-card">
                    <h3><?php esc_html_e('Leads by Status', 'urbana-guild-ledger'); ?></h3>
                    <canvas id="chart-lead-status" width="400" height="160"></canvas>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Fix admin menu highlighting for Lead Status taxonomy pages
     */
    public function fix_menu_highlight() {
        // Ensure the Urbana top-level menu stays active when managing lead_status terms
        $screen = get_current_screen();
        if (!$screen) return;

        if (isset($screen->taxonomy) && $screen->taxonomy === 'lead_status') {
            global $parent_file, $submenu_file;
            $parent_file = 'urbana-main';
            $submenu_file = 'edit-tags.php?taxonomy=lead_status&post_type=' . self::POST_TYPE;
        }
    }
    
    /**
     * Add meta boxes to the post edit screen
     */
    public function add_meta_boxes() {
        add_meta_box(
            'urbana_ledger_details',                        // Meta box ID
            __('Interaction Details', 'urbana-guild-ledger'), // Title
            array($this, 'render_meta_box'),               // Callback
            self::POST_TYPE,                                // Screen
            'normal',                                       // Context
            'high'                                          // Priority
        );
    }
    
    /**
     * Render the meta box content
     */
    public function render_meta_box($post) {
        // Add nonce field for security
        wp_nonce_field('urbana_ledger_meta_box', 'urbana_ledger_meta_box_nonce');
        
        // Get current values
        $contact_name = get_post_meta($post->ID, '_urbana_contact_name', true);
        $company_council = get_post_meta($post->ID, '_urbana_company_council', true);
        $interaction_date = get_post_meta($post->ID, '_urbana_interaction_date', true);
        $interaction_type = get_post_meta($post->ID, '_urbana_interaction_type', true);
        $notes = get_post_meta($post->ID, '_urbana_notes', true);
        
        // Interaction type options
        $interaction_types = array(
            'email'      => __('Email', 'urbana-guild-ledger'),
            'video_call' => __('Video Call', 'urbana-guild-ledger'),
            'in_person'  => __('In-Person', 'urbana-guild-ledger'),
            'phone_call' => __('Phone Call', 'urbana-guild-ledger'),
        );
        ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="urbana_contact_name"><?php esc_html_e('Contact Name', 'urbana-guild-ledger'); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <input type="text" id="urbana_contact_name" name="urbana_contact_name" value="<?php echo esc_attr($contact_name); ?>" class="regular-text" required />
                    <p class="description"><?php esc_html_e('Enter the name of the person you interacted with.', 'urbana-guild-ledger'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="urbana_company_council"><?php esc_html_e('Company/Council', 'urbana-guild-ledger'); ?></label>
                </th>
                <td>
                    <input type="text" id="urbana_company_council" name="urbana_company_council" value="<?php echo esc_attr($company_council); ?>" class="regular-text" />
                    <p class="description"><?php esc_html_e('Enter the organization or council the contact represents.', 'urbana-guild-ledger'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="urbana_interaction_date"><?php esc_html_e('Date', 'urbana-guild-ledger'); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <input type="date" id="urbana_interaction_date" name="urbana_interaction_date" value="<?php echo esc_attr($interaction_date); ?>" required />
                    <p class="description"><?php esc_html_e('Select the date of the interaction.', 'urbana-guild-ledger'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="urbana_interaction_type"><?php esc_html_e('Interaction Type', 'urbana-guild-ledger'); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <select id="urbana_interaction_type" name="urbana_interaction_type" required>
                        <option value=""><?php esc_html_e('Select interaction type...', 'urbana-guild-ledger'); ?></option>
                        <?php foreach ($interaction_types as $key => $label) : ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($interaction_type, $key); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php esc_html_e('Choose the type of interaction that took place.', 'urbana-guild-ledger'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="urbana_lead_status"><?php esc_html_e('Lead Status', 'urbana-guild-ledger'); ?></label>
                </th>
                <td>
                    <?php
                    $lead_status_terms = get_terms(array('taxonomy'=>'lead_status','hide_empty'=>false));
                    $current_status = wp_get_post_terms($post->ID, 'lead_status', array('fields'=>'slugs'));
                    $current_status = !empty($current_status) ? $current_status[0] : '';
                    ?>
                    <select id="urbana_lead_status" name="urbana_lead_status">
                        <option value=""><?php esc_html_e('Select status...', 'urbana-guild-ledger'); ?></option>
                        <?php foreach ($lead_status_terms as $term) : ?>
                            <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($current_status, $term->slug); ?>><?php echo esc_html($term->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php esc_html_e('Assign a lead status to this contact. Manage statuses under Urbana → Lead Statuses.', 'urbana-guild-ledger'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="urbana_notes"><?php esc_html_e('Notes', 'urbana-guild-ledger'); ?></label>
                </th>
                <td>
                    <?php
                    wp_editor(
                        $notes,
                        'urbana_notes',
                        array(
                            'textarea_name' => 'urbana_notes',
                            'media_buttons' => false,
                            'textarea_rows' => 10,
                            'teeny'         => false,
                            'dfw'           => false,
                            'tinymce'       => array(
                                'resize'             => false,
                                'wp_autoresize_on'   => true,
                                'add_unload_trigger' => false,
                            ),
                        )
                    );
                    ?>
                    <p class="description"><?php esc_html_e('Add detailed notes about the conversation and interaction.', 'urbana-guild-ledger'); ?></p>
                </td>
            </tr>
        </table>
        
        <?php
    }
    
    /**
     * Save meta box data
     */
    public function save_meta_boxes($post_id) {
        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check nonce for security (unslash input first)
        $nonce = isset($_POST['urbana_ledger_meta_box_nonce']) ? sanitize_text_field( wp_unslash( $_POST['urbana_ledger_meta_box_nonce'] ) ) : '';
        if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'urbana_ledger_meta_box' ) ) {
            return;
        }

        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Check if this is the correct post type
        if (get_post_type($post_id) !== self::POST_TYPE) {
            return;
        }

        // Work with unslashed post data
        $post_data = wp_unslash( $_POST );

        // Sanitize and save data
        $fields = array(
            'urbana_contact_name'     => 'sanitize_text_field',
            'urbana_company_council'  => 'sanitize_text_field',
            'urbana_interaction_date' => 'sanitize_text_field',
            'urbana_interaction_type' => 'sanitize_text_field',
            'urbana_notes'            => 'wp_kses_post',
        );

        foreach ($fields as $field => $sanitize_function) {
            if (isset($post_data[$field])) {
                $value = call_user_func($sanitize_function, $post_data[$field]);
                update_post_meta($post_id, '_' . $field, $value);
            }
        }

        // Save lead status taxonomy if provided
        if (isset($post_data['urbana_lead_status'])) {
            $status = sanitize_text_field($post_data['urbana_lead_status']);
            // Assign the term (empty string clears terms)
            wp_set_object_terms($post_id, $status ? array($status) : array(), 'lead_status', false);
        }

        // Update post title if contact name is provided
        if (isset($post_data['urbana_contact_name']) && !empty($post_data['urbana_contact_name'])) {
            $contact_name = sanitize_text_field($post_data['urbana_contact_name']);
            $company = isset($post_data['urbana_company_council']) ? sanitize_text_field($post_data['urbana_company_council']) : '';
            $date = isset($post_data['urbana_interaction_date']) ? sanitize_text_field($post_data['urbana_interaction_date']) : '';
            
            // Create a descriptive title
            $title = $contact_name;
            if (!empty($company)) {
                $title .= ' (' . $company . ')';
            }
            if (!empty($date)) {
                $title .= ' - ' . date_i18n('M j, Y', strtotime($date));
            }
            
            // Remove the action to prevent infinite loop
            remove_action('save_post', array($this, 'save_meta_boxes'));
            
            // Update the post title
            wp_update_post(array(
                'ID'         => $post_id,
                'post_title' => $title,
            ));
            
            // Re-add the action
            add_action('save_post', array($this, 'save_meta_boxes'));
        }
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type;
        
        if ($post_type === self::POST_TYPE || $hook === 'toplevel_page_urbana-main') {
            // Enqueue WordPress DataViews dependencies
            wp_enqueue_style('wp-components');
            
            // Enqueue custom admin styles and scripts
            wp_enqueue_style('urbana-guild-ledger-admin', plugin_dir_url(__FILE__) . 'assets/admin.css', array('wp-components'), self::VERSION);
            wp_enqueue_script('urbana-guild-ledger-admin', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery', 'wp-element', 'wp-components', 'wp-data'), self::VERSION, true);

            // Add Chart.js for the dashboard page (use local copy if available; offloading to CDN is disallowed)
            if ($hook === 'toplevel_page_urbana-main') {
                $local_chart = plugin_dir_path(__FILE__) . 'assets/vendor/chart.min.js';
                if ( file_exists( $local_chart ) ) {
                    wp_enqueue_script('chartjs', plugin_dir_url(__FILE__) . 'assets/vendor/chart.min.js', array(), '4.3.0', true);
                } else {
                    // Local copy not found — do not enqueue external CDN in distributed plugin
                    // Developers can add a local copy at assets/vendor/chart.min.js if needed.
                }
            }
            
            // Pass data to JavaScript
            wp_localize_script('urbana-guild-ledger-admin', 'urbanaLedgerData', array(
                'postType' => self::POST_TYPE,
                'nonce' => wp_create_nonce('wp_rest'),
                'restUrl' => rest_url(),
                'adminUrl' => admin_url(),
            ));
        }
    }
    
    /**
     * Set custom columns for the posts table
     */
    public function set_custom_columns($columns) {
        // Remove default columns we don't need
        unset($columns['date']);
        unset($columns['author']);
        
        // Add our custom columns
        $columns['contact_name'] = __('Contact Name', 'urbana-guild-ledger');
        $columns['company_council'] = __('Company/Council', 'urbana-guild-ledger');
        $columns['interaction_date'] = __('Date', 'urbana-guild-ledger');
        $columns['interaction_type'] = __('Interaction Type', 'urbana-guild-ledger');
        $columns['lead_status'] = __('Lead Status', 'urbana-guild-ledger');
        
        return $columns;
    }
    
    /**
     * Display custom column content
     */
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'contact_name':
                echo esc_html(get_post_meta($post_id, '_urbana_contact_name', true));
                break;
                
            case 'company_council':
                echo esc_html(get_post_meta($post_id, '_urbana_company_council', true));
                break;
                
            case 'interaction_date':
                $date = get_post_meta($post_id, '_urbana_interaction_date', true);
                if ($date) {
                    echo esc_html( date_i18n('M j, Y', strtotime($date)) );
                }
                break;
                
            case 'interaction_type':
                $type = get_post_meta($post_id, '_urbana_interaction_type', true);
                $types = array(
                    'email'      => __('Email', 'urbana-guild-ledger'),
                    'video_call' => __('Video Call', 'urbana-guild-ledger'),
                    'in_person'  => __('In-Person', 'urbana-guild-ledger'),
                    'phone_call' => __('Phone Call', 'urbana-guild-ledger'),
                );
                
                if (isset($types[$type])) {
                    echo '<span class="interaction-type interaction-type--' . esc_attr($type) . '">';
                    echo esc_html($types[$type]);
                    echo '</span>';
                }
                break;

            case 'lead_status':
                $terms = wp_get_post_terms($post_id, 'lead_status');
                if (!empty($terms) && isset($terms[0]->name)) {
                    echo esc_html($terms[0]->name);
                } else {
                    echo '';
                }
                break;
        }
    }
    
    /**
     * Make custom columns sortable
     */
    public function sortable_columns($columns) {
        $columns['contact_name'] = 'contact_name';
        $columns['company_council'] = 'company_council';
        $columns['interaction_date'] = 'interaction_date';
        $columns['interaction_type'] = 'interaction_type';
        
        return $columns;
    }
    
    /**
     * Custom orderby for meta fields
     */
    public function custom_orderby($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        
        if ($query->get('post_type') !== self::POST_TYPE) {
            return;
        }
        
        $orderby = $query->get('orderby');
        
        switch ($orderby) {
            case 'contact_name':
                $query->set('meta_key', '_urbana_contact_name');
                $query->set('orderby', 'meta_value');
                break;
                
            case 'company_council':
                $query->set('meta_key', '_urbana_company_council');
                $query->set('orderby', 'meta_value');
                break;
                
            case 'interaction_date':
                $query->set('meta_key', '_urbana_interaction_date');
                $query->set('orderby', 'meta_value');
                break;
                
            case 'interaction_type':
                $query->set('meta_key', '_urbana_interaction_type');
                $query->set('orderby', 'meta_value');
                break;
        }
    }
}

// Initialize the plugin
function urbana_guild_ledger_init() {
    Urbana_Guild_Ledger::get_instance();
}
add_action('plugins_loaded', 'urbana_guild_ledger_init');

// Plugin activation callback
function urbana_guild_ledger_activate() {
    // Ensure the post type and taxonomy are registered using the plugin's registration methods
    $instance = Urbana_Guild_Ledger::get_instance();
    if (is_callable(array($instance, 'register_post_type'))) {
        $instance->register_post_type();
    }
    if (is_callable(array($instance, 'register_taxonomies'))) {
        $instance->register_taxonomies();
    }

    // Default statuses (ensure these terms exist)
    $default_statuses = array('new' => 'New', 'contacted' => 'Contacted', 'qualified' => 'Qualified', 'converted' => 'Converted', 'lost' => 'Lost');
    foreach ($default_statuses as $slug => $name) {
        if (!term_exists($slug, 'lead_status')) {
            wp_insert_term($name, 'lead_status', array('slug' => $slug));
        }
    }

    flush_rewrite_rules();
} 

// Plugin deactivation callback
function urbana_guild_ledger_deactivate() {
    flush_rewrite_rules();
}

// Register activation/deactivation hooks
register_activation_hook(__FILE__, 'urbana_guild_ledger_activate');
register_deactivation_hook(__FILE__, 'urbana_guild_ledger_deactivate');