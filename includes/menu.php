<?php

class RS_Account_Pages_Menu {
	
	private static $current_menu = null;
	
	/**
	 * Initialized when the plugin is loaded
	 *
	 * @return void
	 */
	public static function init() {
		
		// Add menu choices from the settings page to the menu dropdown on the account menu block
		add_filter( 'acf/load_field/key=field_65ece8671a334', array( __CLASS__, 'acf_load_menu_slug_choices' ) );
		
	}
	
	/**
	 * Get the active menu based on the current user, returning the first menu that the user meets the conditions for.
	 *
	 * @param int $user_id
	 *
	 * @return RS_Account_Pages_Menu_Instance|false
	 */
	public static function get_current_menu( $user_id = null ) {
		if ( self::$current_menu === null ) {
			// Get all menus
			$menus = self::get_all_menus();
			
			// Set as false until we find one
			self::$current_menu = false;
			
			// Find the first menu that the user meets the conditions for
			foreach( $menus as $menu ) {
				if ( $menu->user_meets_conditions( $user_id ) ) {
					
					// Set as current menu, then stop the loop
					self::$current_menu = $menu;
					break;
				
				}
			}
		}
		
		// Return current menu
		return self::$current_menu;
	}
	
	/**
	 * Get a menu based on menu slug
	 *
	 * @param string $slug
	 *
	 * @return RS_Account_Pages_Menu_Instance|false
	 */
	public static function get_menu_by_slug( $slug ) {
		$menus = self::get_all_menus();
		return $menus[ $slug ] ?? false;
	}
	
	/**
	 * Get all menus
	 *
	 * @return RS_Account_Pages_Menu_Instance[]
	 */
	public static function get_all_menus() {
		$raw_menus = get_field( 'menus', 'account_settings' );
		$menus = array();
		
		foreach( $raw_menus as $settings ) {
			$menu = new RS_Account_Pages_Menu_Instance( $settings );
			
			if ( $menu->is_valid() ) $menus[ $menu->get_menu_slug() ] = $menu;
		}
		
		return $menus;
	}
	
	/**
	 * Add menu choices from the settings page to the menu dropdown on the account menu block
	 *
	 * @param array $field
	 *
	 * @return array
	 */
	public static function acf_load_menu_slug_choices( $field ) {
		// Do not modify options on the field group editor screen
		if ( acf_is_screen('acf-field-group') ) return $field;
		
		$menus = self::get_all_menus();
		
		// Build choices
		$field['choices'] = array();
		
		$field['choices']['automatic'] = '(Automatic)';
		
		foreach( $menus as $menu ) {
			$count = count( $menu->get_raw_menu_items() );
			
			$label = sprintf(
				'%s (%s)',
				$menu->get_menu_name(),
				sprintf(_n('%s Item', '%s Items', $count), $count)
			);
			
			$field['choices'][ $menu->get_menu_slug() ] = $label;
		}
		
		return $field;
	}
	
}

RS_Account_Pages_Menu::init();