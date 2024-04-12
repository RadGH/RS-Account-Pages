<?php

class RS_Account_Pages_Menu_Instance {
	
	private bool $is_valid = false;
	private bool $is_prepared = false;
	
	private string $menu_name;
	private string $menu_slug;
	private array $conditions;
	private array $menu_items;
	
	/**
	 * Create this menu instance an array of settings from the ACF Settings screen (Account Pages -> Settings)
	 *
	 * @param array $data {
	 *
	 *     @type string $menu_name
	 *     @type string $menu_slug
	 *     @type string[] $conditions  [ 'logged_in', 'logged_out', 'admin', 'not_admin', 'never_show' ]
	 *     @type array $menu_items {
	 *         @type string $type
	 *         @type array  $menu_item {
	 *             @type int    $post_id
	 *             @type string $custom_title
	 *             @type string $custom_url
	 *             @type array  $conditions [ 'logged_in', 'logged_out', 'admin', 'not_admin' ]
	 *         }
	 *         @type array  $options [ 'hidden', 'custom_title' ]
	 *     }
	 * }
	 */
	public function __construct( $data ) {
		$this->menu_name = (string) $data['menu_name'];
		$this->menu_slug = (string) $data['menu_slug'];
		$this->conditions = $data['conditions'];
		$this->menu_items = array();
		
		if ( $data['menu_items'] ) {
			foreach( $data['menu_items'] as $slug => $menu_item ) {
				$this->menu_items[$slug] = array(
					'type'        => $menu_item['type'],
					'post_id'     => $menu_item['menu_item']['post_id'],
					'custom_title'=> $menu_item['menu_item']['custom_title'],
					'custom_url'  => $menu_item['menu_item']['custom_url'],
					'conditions'  => $menu_item['menu_item']['conditions'],
					'options'     => $menu_item['options'],
				);
			}
		}
		
		// Test if valid by checking required fields
		if ( $this->menu_name && $this->menu_slug && !empty($this->menu_items) ) {
			$this->is_valid = true;
		} else {
			$this->is_valid = false;
		}
	}
	
	/**
	 * Check whether this menu is valid, by checking required fields to ensure they are not empty.
	 *
	 * @return bool
	 */
	public function is_valid() {
		return $this->is_valid;
	}
	
	/**
	 * Get the menu name
	 *
	 * @return string
	 */
	public function get_menu_name() {
		return $this->menu_name;
	}
	
	/**
	 * Get the menu slug
	 *
	 * @return string
	 */
	public function get_menu_slug() {
		return $this->menu_slug;
	}
	
	/**
	 * Get the conditions for this menu
	 *
	 * @return string[]
	 */
	public function get_conditions() {
		return $this->conditions;
	}
	
	/**
	 * Check conditions for this menu against the current user, or specific user
	 *
	 * @param int|null $user_id  User ID to check conditions against. Defaults to the current user.
	 *
	 * @return bool
	 */
	public function user_meets_conditions( $user_id = null ) {
		$conditions = $this->get_conditions();
		
		if ( empty( $conditions ) ) {
			
			// No conditions, always true
			return true;
			
		}else{
			
			// Check conditions, returns true if any are met
			return RS_Account_Pages_Utility::user_meets_conditions( $conditions, $user_id );
			
		}
	}
	
	/**
	 * Get the raw menu items for this menu
	 *
	 * @return array {
	 *     @type string $type
	 *     @type int $post_id
	 *     @type string $custom_title
	 *     @type string $custom_url
	 *     @type array $options: 'hidden', 'custom_title'
	 * }
	 */
	public function get_menu_items() {
		if ( ! $this->is_prepared ) $this->prepare_menu();
		
		return $this->menu_items;
	}
	
	/**
	 * Gets menu item data without preparing them first.
	 * Note: If the items have already been prepared, this will return the prepared items.
	 *
	 * @return array
	 */
	public function get_raw_menu_items() {
		return $this->menu_items;
	}
	
	/**
	 * Prepares all menu items for this menu instance, calculating the title, url, classes, and other settings of each item.
	 *
	 * @return array[] {
	 *     [raw]
	 *     @type string   $type
	 *     @type int      $post_id
	 *     @type string   $custom_title
	 *     @type string   $custom_url
	 *     @type array    $conditions: 'logged_in', 'logged_out', 'admin', 'not_admin'
	 *     @type array    $options: 'hidden', 'custom_title'
	 *
	 *     [prepared]
	 *     @type string[] $classes     The classes to apply to the menu item
	 *     @type bool     $enabled     Whether the menu item is enabled
	 *     @type bool     $prepared    True once prepared. Not set otherwise.
	 *     @type string   $error       If the menu item is disabled, this message explains why
	 *     @type bool     $visible     Whether the menu item is visible
	 *     @type string   $url         The URL for the menu item
	 *     @type string   $title       The text for the menu item
	 * }
	 */
	private function prepare_menu() {
		// Prepare the menu only once
		if ( ! $this->is_prepared ) {
			$this->is_prepared = true;
			
			// Prepare each menu item
			foreach( $this->menu_items as $slug => $menu_item ) {
				$this->menu_items[$slug] = $this->prepare_menu_item( $menu_item );
			}
		}
		
		return $this->menu_items;
	}
	
	/**
	 * Prepares a menu item, formatting the title and URL from the raw settings to be used in a menu, redirect, etc.
	 *
	 * @param array $item  The raw menu item {
	 *     @type string $type          The type of menu item. Any of: 'page', 'login', 'logout', 'custom'
	 *     @type string $post_id       The post ID for the menu item. The type must be 'page'.
	 *     @type string $custom_title  The custom title for the menu item. Options must have 'custom_title' or type must be 'custom'.
	 *     @type string $custom_url    The custom URL for the menu item. Options must have 'custom_url' or type must be 'custom'.
	 *     @type string $conditions    Any of: 'logged_in', 'logged_out', 'admin', 'editor', 'author', 'contributor', 'subscriber'
	 *     @type string $options       Any of: 'hidden', 'custom_title', 'conditional', 'custom_url'
	 * }
	 *
	 * @return array {
	 *     [raw]
	 *     @type string   $type
	 *     @type int      $post_id
	 *     @type string   $custom_title
	 *     @type string   $custom_url
	 *     @type array    $conditions: 'logged_in', 'logged_out', 'admin', 'not_admin'
	 *     @type array    $options: 'hidden', 'custom_title'
	 *
	 *     [prepared]
	 *     @type string[] $classes     The classes to apply to the menu item
	 *     @type bool     $enabled     Whether the menu item is enabled
	 *     @type bool     $prepared    True once prepared. Not set otherwise.
	 *     @type string   $error       If the menu item is disabled, this message explains why
	 *     @type bool     $visible     Whether the menu item is visible
	 *     @type string   $url         The URL for the menu item
	 *     @type string   $title       The text for the menu item
	 *
	 * }
	 */
	private static function prepare_menu_item( $item ) {
		// Check if the item is already prepared. Do not prepare it twice.
		if ( ! empty($item['prepared']) ) return $item;
		
		// These are the variables we are trying to fill:
		$formatted_item = array(
			// [raw]
			'type'         => $item['type'],
			'post_id'      => $item['post_id'],
			'custom_title' => $item['custom_title'],
			'custom_url'   => $item['custom_url'],
			'conditions'   => $item['conditions'],
			'options'      => $item['options'],
			
			// [prepared]
			'prepared' => true, // This is used to prevent preparing the same item multiple times
			'enabled'  => true,
			'error'    => '',
			'visible'  => true,
			'classes'  => array(),
			'url'      => false,
			'title'    => false,
			'args'     => array(
				'show_in_menu' => true,
			)
		);
		
		// Properties provided by the raw menu item:
		$type = $item['type'] ?? false;
		$post_id = $item['post_id'] ?? false;
		$custom_title = $item['custom_title'] ?? false;
		$custom_url = $item['custom_url'] ?? false;
		$conditions = $item['conditions'] ?? false;
		$options = $item['options'] ?? false;
		
		// If the type is not set, disable the item
		if ( ! $type ) {
			$formatted_item['enabled'] = false;
			$formatted_item['error'] = 'The menu item `type` is not set.';
			return $formatted_item;
		}
		
		// If the item is hidden, enable it but don't display it
		if ( in_array( 'hidden', $options) ) {
			$formatted_item['visible'] = false;
		}
		
		// If the item has conditions, check those conditions
		if ( in_array( 'conditional', $options) && $conditions ) {
			$meets_conditions = RS_Account_Pages_Utility::user_meets_conditions( $conditions );
			if ( ! $meets_conditions ) {
				// Do not show this menu item because the user does not meet the conditions
				$formatted_item['enabled'] = false;
				$formatted_item['error'] = 'The user does not meet the condition for this menu item: '. implode(', ', $conditions);
				return $formatted_item;
			}
		}
		
		// Apply adjustments by menu item type
		switch( $type ) {
			
			case 'page':
				if ( $post_id ) {
					$formatted_item['title'] = get_the_title( $post_id );
					$formatted_item['url'] = get_permalink( $post_id );
				}else{
					$formatted_item['enabled'] = false;
					$formatted_item['error'] = 'The menu item `post_id` is not set.';
				}
				break;
			
			case 'login':
				$formatted_item['title'] = 'Sign In';
				$formatted_item['url'] = wp_login_url();
				break;
			
			case 'logout':
				$formatted_item['title'] = 'Sign Out';
				$formatted_item['url'] = wp_logout_url();
				break;
			
			case 'custom':
				$formatted_item['title'] = $custom_title;
				$formatted_item['url'] = $custom_url;
				break;
			
		}
		
		// If the item has a custom title, use that instead
		if ( in_array( 'custom_title', $options) && $custom_title ) {
			$formatted_item['title'] = $custom_title;
		}
		
		// If the item has a custom URL, use that instead
		if ( in_array( 'conditional', $options) && $custom_url ) {
			$formatted_item['url'] = $custom_url;
		}
		
		// Disable all URLs in the block editor to prevent accidental navigation
		if ( RS_Account_Pages_Utility::is_block_editor() ) {
			$formatted_item['url'] = false;
		}
		
		// Build classes
		$formatted_item['classes'][] = 'account-menu--item';
		$formatted_item['classes'][] = 'account-menu--item-' . $type;
		
		// If the item is the current page, add a class
		if ( $type == 'page' && $post_id ) {
			$formatted_item['classes'][] = 'post-id--' . $post_id;
			if ( is_singular() && get_the_ID() == $post_id ) {
				$formatted_item['classes'][] = 'current-menu-item';
			}
		}
		
		// If the title is empty, don't display the item
		if ( ! $formatted_item['title'] ) {
			$formatted_item['visible'] = false;
		}
		
		// If the item is not enabled, also mark it as hidden for redundancy
		if ( ! $formatted_item['enabled'] ) {
			$formatted_item['visible'] = false;
		}
		
		return $formatted_item;
	}
	
}