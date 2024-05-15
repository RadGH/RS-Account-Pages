<?php
/*
Plugin Name: RS Account Pages
Description: Adds an Account Pages post type with custom menus that have conditional logic based on the current user.
Version: 1.0.6
Author: Radley Sustaire
Author URI: https://radleysustaire.com
GitHub Plugin URI: https://github.com/RadGH/RS-Account-Pages
Primary Branch: main
*/

define( 'RSAD_PATH', __DIR__ );
define( 'RSAD_URL', untrailingslashit(plugin_dir_url(__FILE__)) );
define( 'RSAD_VERSION', '1.0.6' );

class RS_Account_Pages {
	
	/**
	 * Checks that required plugins are loaded before continuing
	 *
	 * @return void
	 */
	public static function load_plugin() {
		
		// Check for required plugins
		$missing_plugins = array();
		
		if ( ! class_exists('ACF') ) {
			$missing_plugins[] = 'Advanced Custom Fields Pro';
		}
		
		if ( $missing_plugins ) {
			self::add_admin_notice( '<strong>RS Account Pages:</strong> The following plugins are required: '. implode(', ', $missing_plugins) . '.', 'error' );
			return;
		}
		
		// load acf fields
		require_once( RSAD_PATH . '/acf-fields/fields.php' );
		
		// Load plugin files
		require_once( RSAD_PATH . '/includes/advanced/post-type-instance.php' );
		require_once( RSAD_PATH . '/includes/advanced/menu-instance.php' );
		require_once( RSAD_PATH . '/includes/account.php' );
		require_once( RSAD_PATH . '/includes/menu.php' );
		require_once( RSAD_PATH . '/includes/setup.php' );
		require_once( RSAD_PATH . '/includes/utility.php' );
		
	}
	
	/**
	 * When the plugin is activated, set up the post types and refresh permalinks
	 */
	public static function on_plugin_activation() {
		update_option( 'rs_account_pages_flush_rewrite_rules', 1, true );
	}
	
	/**
	 * Adds an admin notice to the dashboard's "admin_notices" hook.
	 *
	 * @param string $message The message to display
	 * @param string $type    The type of notice: info, error, warning, or success. Default is "info"
	 * @param bool $format    Whether to format the message with wpautop()
	 *
	 * @return void
	 */
	public static function add_admin_notice( $message, $type = 'info', $format = true ) {
		add_action( 'admin_notices', function() use ( $message, $type, $format ) {
			?>
			<div class="notice notice-<?php echo $type; ?> rs-utility-blocks-notice">
				<?php echo $format ? wpautop($message) : $message; ?>
			</div>
			<?php
		});
	}
	
	/**
	 * Add a link to the settings page
	 *
	 * @param array $links
	 *
	 * @return array
	 */
	public static function add_settings_link( $links ) {
		$settings_link = '<a href="edit.php?post_type=account-page&page=account-page-settings">Settings</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}
	
}

// Add a link to the settings page
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array('RS_Account_Pages', 'add_settings_link') );

// When the plugin is activated, set up the post types and refresh permalinks
register_activation_hook( __FILE__, array('RS_Account_Pages', 'on_plugin_activation') );

// Initialize the plugin
add_action( 'plugins_loaded', array('RS_Account_Pages', 'load_plugin') );