<?php

class RS_Account_Pages_Utility {
	
	public static function init() {}
	
	/**
	 * Check if the page is for the block editor
	 *
	 * @return bool
	 */
	public static function is_block_editor() {
		static $is_block_editor = null;
		
		if ( $is_block_editor === null ) {
			if ( is_admin() ) {
				$is_block_editor = true;
			}else if ( function_exists('acf_is_block_editor') && acf_is_block_editor() ) {
				$is_block_editor = true;
			}else{
				$is_block_editor = false;
			}
		}
		
		return $is_block_editor;
	}
	/**
	 * Get the attributes for a block, particularly the ID and class attributes.
	 *
	 * @param array $block
	 * @param array $atts {
	 *     @type string          $id     Optional. The ID of the element. Will replace the block "anchor".
	 *     @type string|string[] $class  Optional. Additional classes to add to the block.
	 * }
	 *
	 * @return void
	 */
	public static function output_block_attributes( $block, $atts = array() ) {
		$atts = self::get_block_attributes( $block, $atts );
		
		// Output each attribute in the list
		foreach( $atts as $key => $value ) {
			if ( $key && $value ) {
				echo $key .'="'. esc_attr($value) .'" ';
			}
		}
	}
	
	/**
	 * Output the attributes for a block, particularly the ID and class attributes.
	 *
	 * @param array $block
	 * @param array $atts {
	 *     @type string          $id     Optional. The ID of the element. Will replace the block "anchor".
	 *     @type string|string[] $class  Optional. Additional classes to add to the block.
	 * }
	 *
	 * @return string[]
	 */
	public static function get_block_attributes( $block, $atts = array() ) {
		$list = array();
		
		// Get the block ID, or load it from the block settings.
		if ( isset($atts['id']) ) {
			$list['id'] = $atts['id'];
		}else{
			$list['id'] = self::get_block_id( $block );
		}
		
		// Get the block classes and combine them with any additional classes.
		$list['class'] = self::get_block_classes( $block );
		
		// Merge custom classes with block classes
		if ( isset($atts['class']) ) {
			$list['class'] = array_merge( (array) $atts['class'], $list['class'] );
		}
		
		// Convert classes to a string
		if ( !empty( $list['class'] ) && is_array($list['class']) ) {
			$list['class'] = implode(' ', $list['class']);
		}
		
		// Add custom attributes
		foreach( $atts as $key => $value ) {
			if ( $key !== 'id' && $key !== 'class' ) {
				$list[$key] = $value;
			}
		}
		
		return $list;
	}
	
	/**
	 * Get the ID of a block (if specified)
	 *
	 * @param array $block
	 *
	 * @return false|mixed
	 */
	public static function get_block_id( $block ) {
		if ( ! empty( $block['anchor'] ) ) {
			return $block['anchor'];
		}
		
		return false;
	}
	
	/**
	 * Get the classes of a block
	 *
	 * @param array $block
	 *
	 * @return array
	 */
	public static function get_block_classes( $block ) {
		$classes = array();
		
		// Create class attribute allowing for custom "className" and "align" values.
		if ( ! empty( $block['className'] ) ) {
			$classes[] = $block['className'];
		}
		
		if ( ! empty( $block['align'] ) ) {
			$classes[] = 'align' . $block['align'];
		}
		
		// Text alignment - this is a custom class
		if ( ! empty( $block['alignText'] ) ) {
			$classes[] = 'has-text-align-' . $block['align'];
		}
		
		if ( ! empty( $block['backgroundColor'] ) ) {
			// Default classes, but acf block does not seem to apply them :/
			$classes[] = 'has-background';
			$classes[] = 'has-'. $block['backgroundColor'] .'-background-color';
		}
		
		if ( ! empty( $block['textColor'] ) ) {
			// Default classes, but acf block does not seem to apply them :/
			$classes[] = 'has-text-color';
			$classes[] = 'has-'. $block['textColor'] .'-color';
		}
		
		// Remove duplicate classes
		if ( ! empty( $classes ) ) {
			$classes = array_unique( $classes );
		}
		
		return $classes;
	}
	
	/**
	 * Check if the user meets the conditions for a menu
	 *
	 * @param int $user_id
	 * @param string[] $conditions: 'logged_in', 'logged_out', 'admin', 'editor', 'author', 'contributor', 'subscriber'
	 *
	 * @return bool
	 */
	public static function user_meets_conditions( $conditions, $user_id = null ) {
		if ( $user_id === null ) $user_id = get_current_user_id();
		
		$meets_conditions = false;
		
		if ( $conditions ) foreach( $conditions as $condition ) {
			if ( self::user_meets_condition( $user_id, $condition ) ) {
				$meets_conditions = true;
				break;
			}
		}
		
		return $meets_conditions;
	}
	
	/**
	 * Check if the user meets a single condition
	 *
	 * @param int $user_id
	 * @param string $condition: 'logged_in', 'logged_out', 'admin', 'editor', 'author', 'contributor', 'subscriber'
	 *
	 * @return bool
	 */
	public static function user_meets_condition( $user_id, $condition ) {
		$meets_condition = false;
		$invert = false;
		
		// If the condition starts with "not_", invert the result
		if ( str_starts_with( $condition, 'not_' ) ) {
			$condition = substr( $condition, 4 );
			$invert = true;
		}
		
		switch( $condition ) {
			case 'logged_in':
				$meets_condition = is_user_logged_in();
				break;
			case 'logged_out':
				$meets_condition = ! is_user_logged_in();
				break;
			case 'admin':
				$meets_condition = user_can( $user_id, 'manage_options' );
				break;
			case 'editor':
				$meets_condition = user_can( $user_id, 'edit_others_posts' );
				break;
			case 'author':
				$meets_condition = user_can( $user_id, 'edit_published_posts' );
				break;
			case 'contributor':
				$meets_condition = user_can( $user_id, 'edit_posts' );
				break;
			case 'subscriber':
				$meets_condition = user_can( $user_id, 'read' );
				break;
			case 'never':
			case 'never_show':
				$meets_condition = false;
				break;
		}
		
		if ( $invert ) {
			$meets_condition = ! $meets_condition;
		}
		
		return $meets_condition;
	}
	
}

RS_Account_Pages_Utility::init();