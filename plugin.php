<?php
/*
	Plugin Name: IMPress Listings
	Plugin URI: http://wordpress.org/plugins/wp-listings/
	Description: Creates a real estate listing management system. Designed to work with any theme using built-in templates.
	Author: Agent Evolution
	Author URI: http://agentevolution.com
	Text Domain: wp-listings

	Version: 2.6.2

	License: GNU General Public License v2.0 (or later)
	License URI: http://www.opensource.org/licenses/gpl-license.php
*/

register_activation_hook( __FILE__, 'wp_listings_activation' );
/**
 * This function runs on plugin activation. It flushes the rewrite rules to prevent 404's
 *
 * @since 0.1.0
 */
function wp_listings_activation() {

	/** Flush rewrite rules */
	if ( ! post_type_exists( 'listing' ) ) {
		wp_listings_init();
		global $_wp_listings, $_wp_listings_taxonomies, $_wp_listings_templates;
		$_wp_listings->create_post_type();
		$_wp_listings_taxonomies->register_taxonomies();
	}
	flush_rewrite_rules();

	$notice_keys = array('wpl_notice_idx', 'wpl_listing_notice_idx');
	foreach ($notice_keys as $notice) {
		delete_user_meta( get_current_user_id(), $notice );
	}
}

register_deactivation_hook( __FILE__, 'wp_listings_deactivation' );
/**
 * This function runs on plugin deactivation. It flushes the rewrite rules to get rid of remnants
 *
 * @since 1.0.8
 */
function wp_listings_deactivation() {

	flush_rewrite_rules();

	$notice_keys = array('wpl_notice_idx', 'wpl_listing_notice_idx');
	foreach ($notice_keys as $notice) {
		delete_user_meta( get_current_user_id(), $notice );
	}
}

add_action( 'after_setup_theme', 'wp_listings_init' );
/**
 * Initialize IMPress Listings.
 *
 * Include the libraries, define global variables, instantiate the classes.
 *
 * @since 0.1.0
 */
function wp_listings_init() {

	global $_wp_listings, $_wp_listings_taxonomies, $_wp_listings_templates;

	define( 'BASE_PLUGINS_DIR', plugin_dir_path( __DIR__ ) );
	define( 'WP_LISTINGS_URL', plugin_dir_url( __FILE__ ) );
	define( 'WP_LISTINGS_DIR', plugin_dir_path( __FILE__ ) );
	define( 'WP_LISTINGS_VERSION', '2.4.1' );

	/** Load textdomain for translation */
	load_plugin_textdomain( 'wp-listings', false, basename( dirname( __FILE__ ) ) . '/languages/' );

	/** Includes */
	require_once( dirname( __FILE__ ) . '/includes/helpers.php' );
	require_once( dirname( __FILE__ ) . '/includes/functions.php' );
	require_once( dirname( __FILE__ ) . '/includes/shortcodes.php' );
	require_once( dirname( __FILE__ ) . '/includes/class-listings.php' );
	require_once( dirname( __FILE__ ) . '/includes/class-listing-import.php' );
	require_once( dirname( __FILE__ ) . '/includes/class-taxonomies.php' );
	require_once( dirname( __FILE__ ) . '/includes/class-listing-template.php' );
	require_once( dirname( __FILE__ ) . '/includes/class-listings-search-widget.php' );
	require_once( dirname( __FILE__ ) . '/includes/class-featured-listings-widget.php' );
	require_once( dirname( __FILE__ ) . '/includes/class-admin-notice.php' );
	require_once( dirname( __FILE__ ) . '/includes/wp-api.php' );
	require_once( dirname( __FILE__ ) . '/includes/integrations/wpl-google-my-business.php' );
	WPL_Google_My_Business::get_instance();

	/** Add theme support for post thumbnails if it does not exist */
	if(!current_theme_supports('post-thumbnails')) {
		add_theme_support( 'post-thumbnails' );
	}

	/** Registers and enqueues scripts for single listings */
	add_action('wp_enqueue_scripts', 'add_wp_listings_scripts');
	function add_wp_listings_scripts() {
		wp_register_script( 'wp-listings-single', WP_LISTINGS_URL . 'includes/js/single-listing.min.js', array('jquery'), null, true ); // enqueued only on single listings
		wp_register_script( 'jquery-validate', WP_LISTINGS_URL . 'includes/js/jquery.validate.min.js', array('jquery'), null, true ); // enqueued only on single listings
		wp_register_script( 'fitvids', '//cdnjs.cloudflare.com/ajax/libs/fitvids/1.1.0/jquery.fitvids.min.js', array('jquery'), null, true ); // enqueued only on single listings
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-tabs', array('jquery') );
    }

	/** Enqueues wp-listings.css style file if it exists and is not deregistered in settings */
	add_action('wp_enqueue_scripts', 'add_wp_listings_main_styles');
	function add_wp_listings_main_styles() {

		$options = get_option('plugin_wp_listings_settings');

		/** Register single styles but don't enqueue them **/
		wp_register_style('wp-listings-single', WP_LISTINGS_URL . 'includes/css/wp-listings-single.css?v=1.0.0', '', null, 'all');

		/** Register Font Awesome icons but don't enqueue them */
		wp_register_style( 'font-awesome-5.8.2', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.8.2/css/all.min.css', array(), '5.8.2', 'all' );


		/** Register Properticons but don't enqueue them */
		wp_register_style('properticons', '//s3.amazonaws.com/properticons/css/properticons.css', '', null, 'all');

		if ( !isset($options['wp_listings_stylesheet_load']) ) {
			$options['wp_listings_stylesheet_load'] = 0;
		}

		if ('1' == $options['wp_listings_stylesheet_load'] ) {
			return;
		}

        if ( file_exists(dirname( __FILE__ ) . '/includes/css/wp-listings.css') ) {
        	wp_register_style('wp_listings', WP_LISTINGS_URL . 'includes/css/wp-listings.css', '', null, 'all');
            wp_enqueue_style('wp_listings');
        }
    }

	/** Enqueues wp-listings-widgets.css style file if it exists and is not deregistered in settings */
	add_action('wp_enqueue_scripts', 'add_wp_listings_widgets_styles');
	function add_wp_listings_widgets_styles() {

		$options = get_option('plugin_wp_listings_settings');

		if ( !isset($options['wp_listings_widgets_stylesheet_load']) ) {
			$options['wp_listings_widgets_stylesheet_load'] = 0;
		}

		if ('1' == $options['wp_listings_widgets_stylesheet_load'] ) {
			return;
		}

		if ( file_exists(dirname( __FILE__ ) . '/includes/css/wp-listings-widgets.css') ) {
			wp_register_style('wp_listings_widgets', WP_LISTINGS_URL . 'includes/css/wp-listings-widgets.css', '', null, 'all');
				wp_enqueue_style('wp_listings_widgets');
		}
	}

	/** Add admin scripts and styles */
	function wp_listings_admin_scripts_styles() {
		$screen_id = get_current_screen();
		if ( 'listing_page_wp-listings-settings' === $screen_id->id || 'listing_page_wp-listings-gmb-settings' === $screen_id->id ) {
			wp_enqueue_script( 'jquery-ui-tabs' );
			wp_enqueue_style( 'jquery-ui-css', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css' );
		}

		if ( 'listing_page_wp-listings-gmb-settings' === $screen_id->id ) {
			$gmb_options = WPL_Google_My_Business::get_instance()->wpl_get_gmb_settings_options();

			wp_enqueue_media();
			wp_register_script( 'impress-gmb-settings', WP_LISTINGS_URL . 'assets/google-my-business-settings.min.js', [], '1.0', true );
			wp_localize_script(
				'impress-gmb-settings',
				'impressGmbAdmin',
				[
					'wp_resource_url'                  => WP_LISTINGS_URL,
					'nonce-gmb-post-now'               => wp_create_nonce( 'impress_gmb_post_now_nonce' ),
					'nonce-gmb-clear-scheduled-posts'  => wp_create_nonce( 'wpl_clear_scheduled_posts_nonce' ),
					'nonce-gmb-get-listing-posts'      => wp_create_nonce( 'impress_gmb_get_listing_posts_nonce' ),
					'nonce-gmb-remove-from-schedule'   => wp_create_nonce( 'impress_gmb_remove_from_schedule_nonce' ),
					'nonce-gmb-update-post-frequency'  => wp_create_nonce( 'impress_gmb_change_posting_frequency_nonce' ),
					'nonce-gmb-dismiss-banner'         => wp_create_nonce( 'impress_gmb_dismiss_banner_nonce' ),
					'nonce-gmb-save-custom-post'       => wp_create_nonce( 'impress_gmb_save_custom_post_nonce' ),
					'nonce-gmb-delete-custom-post'     => wp_create_nonce( 'impress_gmb_delete_custom_post_nonce' ),
					'nonce-gmb-get-posts-data'         => wp_create_nonce( 'impress_gmb_get_posts_data_nonce' ),
					'nonce-gmb-update-scheduled-posts' => wp_create_nonce( 'impress_gmb_update_scheduled_posts_nonce' ),
					// Initial values for frontend.
					'next-scheduled-post-date'         => wp_next_scheduled( 'wp_listings_gmb_auto_post' ),
					'auto-post-frequency'              => $gmb_options['posting_frequency'],
					'instruction-banner-dismissed'     => ( ! empty( $gmb_options['banner_dismissed'] ) ? true : false ),
				]
			);

		}

		wp_enqueue_style( 'wp_listings_admin_css', WP_LISTINGS_URL . 'includes/css/wp-listings-admin.css' );

		/** Enqueue Font Awesome in the Admin if IDX Broker is not installed */
		if (!class_exists( 'Idx_Broker_Plugin' )) {
			wp_enqueue_style( 'font-awesome-5.8.2', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.8.2/css/all.min.css', array(), '5.8.2' );
			wp_enqueue_style( 'upgrade-icon', WP_LISTINGS_URL . 'includes/css/wp-listings-upgrade.css' );
		}

		global $wp_version;
		$nonce_action = 'wp_listings_admin_notice';

		wp_enqueue_style( 'wp-listings-admin-notice', WP_LISTINGS_URL . 'includes/css/wp-listings-admin-notice.css' );
		wp_enqueue_script( 'wp-listings-admin', WP_LISTINGS_URL . 'includes/js/admin.js', 'media-views' );
		wp_localize_script( 'wp-listings-admin', 'wp_listings_adminL10n', array(
			'ajaxurl'                            => admin_url( 'admin-ajax.php' ),
			'nonce'                              => wp_create_nonce( $nonce_action ),
			'wp_version'                         => $wp_version,
			'dismiss'                            => __( 'Dismiss this notice', 'wp-listings' ),
			'nonce-gmb-logout'                   => wp_create_nonce( 'impress_gmb_logout_nonce' ),
			'nonce-gmb-update-location-settings' => wp_create_nonce( 'impress_gmb_update_location_settings_nonce' ),
			'nonce-gmb-reset-post-time'          => wp_create_nonce( 'wpl_reset_next_post_time_request_nonce' ),
			'nonce-gmb-clear-last-post-status'   => wp_create_nonce( 'wpl_clear_last_post_status_nonce' ),
			'nonce-impress-listings-data-optout' => wp_create_nonce( 'impress_listings_data_optout_nonce' ),
		) );

		$localize_script = array(
			'title'        => __( 'Set Term Image', 'wp-listings' ),
			'button'       => __( 'Set term image', 'wp-listings' )
		);

		/* Pass custom variables to the script. */
		wp_localize_script( 'wp-listings-admin', 'wpl_term_image', $localize_script );

		wp_enqueue_media();

	}
	add_action( 'admin_enqueue_scripts', 'wp_listings_admin_scripts_styles' );



	/** Instantiate */
	$_wp_listings = new WP_Listings;
	$_wp_listings_taxonomies = new WP_Listings_Taxonomies;
	$_wp_listings_templates = new Single_Listing_Template;

	add_action( 'widgets_init', 'wp_listings_register_widgets' );

	/**
	 * Function to add admin notices
	 * @param  string  $message    the error messag text
	 * @param  boolean $error      html class - true for error false for updated
	 * @param  string  $cap_check  required capability
	 * @param  boolean $ignore_key ignore key
	 * @return string              HTML of admin notice
	 *
	 * @since  1.3
	 */
	function wp_listings_admin_notice( $message, $error = false, $cap_check = 'activate_plugins', $ignore_key = false ) {
		$_wp_listings_admin = new WP_Listings_Admin_Notice;
		return $_wp_listings_admin->notice( $message, $error, $cap_check, $ignore_key );
	}

	/**
	 * Admin notice AJAX callback
	 * @since  1.3
	 */
	add_action( 'wp_ajax_wp_listings_admin_notice', 'wp_listings_admin_notice_cb' );
	function wp_listings_admin_notice_cb() {
		$_wp_listings_admin = new WP_Listings_Admin_Notice;
		return $_wp_listings_admin->ajax_cb();
	}

}

/**
 * Register Widgets that will be used in the WP Listings plugin
 *
 * @since 0.1.0
 */
function wp_listings_register_widgets() {

	$widgets = array( 'WP_Listings_Featured_Listings_Widget', 'WP_Listings_Search_Widget' );

	foreach ( (array) $widgets as $widget ) {
		register_widget( $widget );
	}

}

/**
 * Google My Business feature notification for Platinum IDXB users.
 *
 * @since 2.6.0
 */
function gmb_dashboard_notice() {
	if ( ! class_exists( 'Idx_Broker_Plugin' ) ) {
		return;
	}
	global $pagenow;
	$idx_api = new \IDX\Idx_Api();
	if ( 'index.php' === $pagenow && $idx_api->platinum_account_type() ) {
		echo wp_listings_admin_notice( __( '<strong><span style="color:green;">New!</span> Connect IMPress Listings to your verified Google My Business profile to generate and schedule timely posts and photos of your listings. <a href="https://wordpress.org/plugins/wp-listings/" target="_blank">Learn more!</a></strong>', 'wp-listings' ), false, 'manage_categories', 'wpl_gmb_feature_notice' );
	}
}
add_action( 'admin_notices', 'gmb_dashboard_notice' );

/**
 * IMPress Listings Get Install Info.
 *
 * @since 2.6.1
 */
function impress_listings_get_install_info() {
	// Return early if IMPress for IDXB is installed and active or if optout is enabled.
	if ( class_exists( 'IDX_Broker_Plugin' ) || get_option( 'impress_data_optout' ) ) {
		return;
	}

	$current_info_version         = '1.0.0';
	$previously_sent_info_version = get_option( 'impress_data_sent' );
	if ( empty( $previously_sent_info_version ) || version_compare( $previously_sent_info_version, $current_info_version ) < 0 ) {
		global $wpdb;
		$install_info = [
			'php_version'       => phpversion(),
			'wordpress_version' => get_bloginfo( 'version' ),
			'theme_name'        => wp_get_theme()->get( 'Name' ),
			'db_version'        => $wpdb->dbh->server_info,
			'memory_limit'      => WP_MEMORY_LIMIT,
			'api_key'           => get_option( 'idx_broker_apikey' ),
			'site_url'          => get_site_url(),
			'impress_listings'  => true,
			'impress_agents'    => class_exists( 'IMPress_Agents' ),
			'impress_idxb'      => false,
		];

		$response = wp_remote_post(
			'https://hsstezluih.execute-api.us-east-1.amazonaws.com/v1/wp-data',
			[
				'headers' => [
					'Content-Type' => 'application/json',
				],
				'body'    => wp_json_encode( $install_info ),
			]
		);

		if ( ! is_wp_error( $response ) ) {
			$response_code = wp_remote_retrieve_response_code( $response );
			if ( 200 === $response_code ) {
				update_option( 'impress_data_sent', $current_info_version );
			}
		}
	}
}
add_action( 'admin_init', 'impress_listings_get_install_info' );

/**
 * IMPress Listings Data Opt-Out.
 *
 * @since 2.6.1
 */
function impress_listings_data_optout() {
	// User capability check.
	if ( ! current_user_can( 'publish_posts' ) || ! current_user_can( 'edit_posts' ) ) {
		echo 'check permissions';
		wp_die();
	}
	// Validate and process request.
	if ( isset( $_POST['nonce'], $_POST['optout'] ) && wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'impress_listings_data_optout_nonce' ) ) {
		update_option( 'impress_data_optout', rest_sanitize_boolean( wp_unslash( $_POST['optout'] ) ) );
		echo 'success';
	}
	wp_die();
}
add_action( 'wp_ajax_impress_listings_data_optout', 'impress_listings_data_optout' );
