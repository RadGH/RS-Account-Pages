<?php

class RS_Account_Pages_Account {
	
	// Post type settings
	private static $post_type_instance;
	
	public static $post_type = 'account-page';
	
	public static $exclude_from_sitemap = true;
	
	public static $post_type_args = array(
		'labels' => array(
			'name' => 'Account Pages',
			'singular_name' => 'Account Page',
			'menu_name' => 'Account Pages',
			'all_items' => 'All Account Pages',
			'edit_item' => 'Edit Account Page',
			'view_item' => 'View Account Page',
			'view_items' => 'View Account Pages',
			'add_new_item' => 'Add New Account Page',
			'add_new' => 'Add New Page',
			'new_item' => 'New Account Page',
			'parent_item_colon' => 'Parent Account Page:',
			'search_items' => 'Search Account Pages',
			'not_found' => 'No account pages found',
			'not_found_in_trash' => 'No account pages found in Trash',
			'archives' => 'Account Page Archives',
			'attributes' => 'Account Page Attributes',
			'insert_into_item' => /** @lang text */ 'Insert into account page',
			'uploaded_to_this_item' => 'Uploaded to this account page',
			'filter_items_list' => 'Filter account pages list',
			'filter_by_date' => 'Filter account pages by date',
			'items_list_navigation' => 'Account Pages list navigation',
			'items_list' => 'Account Pages list',
			'item_published' => 'Account Page published.',
			'item_published_privately' => 'Account Page published privately.',
			'item_reverted_to_draft' => 'Account Page reverted to draft.',
			'item_scheduled' => 'Account Page scheduled.',
			'item_updated' => 'Account Page updated.',
			'item_link' => 'Account Page Link',
			'item_link_description' => 'A link to a account page.',
			'enter_title_here' => 'Enter Account Page Title', // Custom arg. Placeholder text for the title input field.
		),
		'public' => true,
		'exclude_from_search' => true,
		'show_in_nav_menus' => true,
		'show_in_rest' => true,
		'menu_icon' => 'dashicons-admin-users',
		'supports' => array( 'title', 'editor', 'revisions' ),
		'has_archive' => true,
		'rewrite' => array(
			'feeds' => false,
			// Change the rewrite to /account/
			'slug' => 'account',
		),
		'delete_with_user' => false,
	);
	
	/**
	 * Initialized when the plugin is loaded
	 *
	 * @return void
	 */
	public static function init() {
		
		// Register account page post type
		self::$post_type_instance = new RS_Account_Pages_Post_Type_Instance(
			self::$post_type,
			self::$post_type_args,
			self::$exclude_from_sitemap
		);
		
		// Add settings page with ACF
		add_action( 'acf/init', array( __CLASS__, 'add_acf_settings_page' ) );
		
		// Redirect the account page archive to the user's first account page
		add_action( 'template_redirect', array( __CLASS__, 'redirect_account_page_archive' ) );
	
		// Add custom column for "Protection" (protect_page) field
		add_filter( 'manage_'. self::$post_type .'_posts_columns', array( __CLASS__, 'add_custom_columns' ) );
		
		// Display the "Protection" (protect_page) field in the custom column
		add_action( 'manage_'. self::$post_type .'_posts_custom_column', array( __CLASS__, 'display_custom_columns' ), 10, 2 );
		
		// When viewing a page, check if the user can access it
		add_action( 'template_redirect', array( __CLASS__, 'check_page_access' ) );
		
	}
	
	/**
	 * Add settings page with ACF
	 *
	 * @return void
	 */
	public static function add_acf_settings_page() {
		if ( function_exists( 'acf_add_options_sub_page' ) ) {
			acf_add_options_sub_page( array(
				'parent_slug' => 'edit.php?post_type=' . self::$post_type,
				'page_title' => 'Account Page Settings',
				'menu_title' => 'Settings',
				'menu_slug' => 'account-page-settings',
				'capability' => 'manage_options',
				'position' => 2,
				'icon_url' => 'dashicons-admin-users',
				'post_id' => 'account_settings', // get_field( 'something', 'account_settings' );
				'redirect' => false
			) );
		}
	}
	
	/**
	 * Redirect the account page archive to the user's first account page
	 *
	 * @return void
	 */
	public static function redirect_account_page_archive() {
		if ( ! is_post_type_archive( self::$post_type ) ) return;
		
		$menu = RS_Account_Pages_Menu::get_current_menu();
		if ( ! $menu ) return;
		
		// Redirect to the first valid menu item
		foreach( $menu->get_menu_items() as $menu_item ) {
			wp_redirect( $menu_item['url'] );
			exit;
		}
		
		// If not redirected, display an error that your account page is not available
		wp_die( 'Your account dashboard is not currently available.', 'Account Not Available', array( 'response' => 404 ) );
		exit;
	}
	
	/**
	 * Add custom column for "Protection" (protect_page) field
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public static function add_custom_columns( $columns ) {
		$columns['protect_page'] = 'Protection';
		return $columns;
	}
	
	/**
	 * Display the "Protection" (protect_page) field in the custom column
	 *
	 * @param string $column
	 * @param int $post_id
	 *
	 * @return void
	 */
	public static function display_custom_columns( $column, $post_id ) {
		if ( $column == 'protect_page' ) {
			if ( self::is_page_protected( $post_id ) ) {
				echo '<span class="dashicons dashicons-shield"></span>';
				echo ' Protected';
			}else{
				echo '<span class="dashicons dashicons-unlock"></span>';
				echo ' Public';
			}
		}
	}
	
	/**
	 * Check if a page has protection enabled.
	 * To check if a user can access the page with protection,
	 * @see RS_Account_Pages_Account::can_access_page()
	 *
	 * @param int $post_id
	 *
	 * @return bool
	 */
	public static function is_page_protected( $post_id ) {
		return (bool) get_field( 'protect_page', $post_id );
	}
	
	/**
	 * Check if a user can access a page with protection enabled
	 *
	 * @param int $post_id
	 * @param int $user_id
	 *
	 * @return bool
	 */
	public static function can_access_page( $post_id, $user_id = null ) {
		// If the page is not protected, the user can access it
		if ( ! self::is_page_protected( $post_id ) ) return true;
		
		// If the user is an admin, they can access the page
		// if ( user_can( $user_id, 'manage_options' ) ) return true;
		
		// Get the user's current menu
		$menu = RS_Account_Pages_Menu::get_current_menu( $user_id );
		
		// Check if the menu contains this page (even if it is set to be hidden)
		if ( $menu ) {
			foreach( $menu->get_menu_items() as $menu_item ) {
				if ( $menu_item['post_id'] && (int) $post_id === (int) $menu_item['post_id'] ) {
					
					// Menu item found, page is accessible
					return true;
					
				}
			}
		}
		
		// The user cannot access the page
		return false;
	}
	
	/**
	 * When viewing a page, check if the user can access it
	 *
	 * @return void
	 */
	public static function check_page_access() {
		if ( ! is_singular( self::$post_type ) ) return;
		
		$post_id = get_the_ID();
		$user_id = get_current_user_id();
		
		if ( ! self::can_access_page( $post_id, $user_id ) ) {
			
			if ( has_filter( 'rs_account_pages/no_access' ) ) {
				
				// Allow plugins to override the default page protection behavior
				do_action( 'rs_account_pages/no_access', $post_id, $user_id );
				
			}else{
				
				// If not hooked, display an error message and die
				wp_die( 'You do not have permission to access this page.', 'Access Denied', array( 'response' => 403 ) );
				exit;
				
			}
			
		}
	}
	
}

RS_Account_Pages_Account::init();