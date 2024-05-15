<?php

class RS_Account_Pages_Setup {
	
	/**
	 * @var bool  Default true. Set to false to use filemtime() for asset versions, for development purposes.
	 */
	public static $use_plugin_version = false;
	
	public static function init() {
		
		// After the plugin has been activated, flush rewrite rules once
		add_action( 'admin_init', array( __CLASS__, 'flush_rewrite_rules' ) );
		
		// Register (but do not enqueue) CSS and JS files
		add_action( 'init', array( __CLASS__, 'register_all_assets' ) );
		
		// Enqueue assets on the front-end.
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
		
		// Enqueue assets on the front-end.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_public_assets' ) );
		
		// Enqueue scripts for the block editor
		add_action( 'enqueue_block_assets', array( __CLASS__, 'enqueue_block_assets' ) );
		
		// Add body classes for the theme.
		add_filter( 'body_class', array( __CLASS__, 'add_body_classes' ) );
		
		// Register custom block types
		add_action( 'init', array( __CLASS__, 'register_blocks' ) );
		
	}
	
	/**
	 * Flush rewrite rules if the option is set
	 *
	 * @return void
	 */
	public static function flush_rewrite_rules() {
		/**
		 * This option is set to 1 when the plugin is activated.
		 * @see RS_Account_Pages::on_plugin_activation()
		 */
		if ( get_option( 'rs_account_pages_flush_rewrite_rules' ) ) {
			flush_rewrite_rules();
			delete_option( 'rs_account_pages_flush_rewrite_rules' );
		}
	}
	
	/**
	 * Enqueue public scripts (theme/front-end)
	 *
	 * @return void
	 */
	public static function register_all_assets() {
		
		if ( self::$use_plugin_version ) {
			$version = RSAD_VERSION;
		}else{
			$version = max(
				filemtime( RSAD_PATH . '/assets/blocks.css' ),
				filemtime( RSAD_PATH . '/assets/admin.css' )
			);
		}
		
		// Admin CSS
		wp_register_style( 'zingmap-dashboard-admin', RSAD_URL . '/assets/admin.css', array(), $version );
		
		// Block editor CSS
		wp_register_style( 'zingmap-dashboard-blocks', RSAD_URL . '/assets/blocks.css', array(), $version );
		
		// Block editor JS (admin-only)
		// - compiled using "npm run build", see readme.md for details.
		// $asset = require( RSAD_PATH . '/assets/scripts/dist/zingmap-dashboard-block-editor.asset.php' );
		// wp_register_script( 'zingmap-dashboard-blocks-block-editor', RSAD_URL . '/assets/scripts/dist/zingmap-dashboard-block-editor.js', $asset['dependencies'], $asset['version'] );
		
	}
	
	/**
	 * Enqueue assets on the WordPress dashboard (backend).
	 *
	 * @return void
	 */
	public static function enqueue_admin_assets() {
		
		wp_enqueue_style( 'zingmap-dashboard-admin' );
		
	}
	
	/**
	 * Enqueue assets on the front-end.
	 *
	 * @return void
	 */
	public static function enqueue_public_assets() {
		
		wp_enqueue_style( 'zingmap-dashboard-blocks' );
		
	}
	
	/**
	 * Enqueue block editor assets, wherever blocks are used
	 *
	 * @return void
	 */
	public static function enqueue_block_assets() {
		
		wp_enqueue_style( 'zingmap-dashboard-blocks' );
		
		// wp_enqueue_script( 'zingmap-dashboard-blocks-block-editor' );
		
	}
	
	/**
	 * Add body classes for the theme.
	 *
	 * @param array $classes
	 *
	 * @return array
	 */
	public static function add_body_classes( $classes ) {
		// $classes[] = 'something';
		
		return $classes;
	}
	
	/**
	 * Register custom block types
	 */
	public static function register_blocks( $classes ) {
		
		register_block_type( RSAD_PATH . '/blocks/account-menu/block.json');
		
	}
	
}

RS_Account_Pages_Setup::init();