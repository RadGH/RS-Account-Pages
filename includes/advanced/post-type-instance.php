<?php

// This abstract class is used by each post type.
// It provides a common interface for registering post types.
// It also provides a common interface for removing unwanted meta boxes and columns.

class RS_Account_Pages_Post_Type_Instance {
	
	// Settings to override in child classes
	private string $post_type;
	private array  $post_type_args;
	private bool   $exclude_from_sitemap;
	
	public function __construct( $post_type, $post_type_args, $exclude_from_sitemap ) {
		
		$this->post_type = $post_type;
		$this->post_type_args = $post_type_args;
		$this->exclude_from_sitemap = $exclude_from_sitemap;
		
		// Register post type
		add_action( 'init', array( $this, 'register_custom_post_type' ), 5 );
		
		// Customize the "Add title" placeholder text
		add_filter( 'enter_title_here', array( $this, 'customize_title_placeholder' ), 10, 2 );
		
		// Remove unwanted meta boxes from edit screen
		add_action( 'add_meta_boxes', array( $this, 'remove_unwanted_meta_boxes' ), 30 );
		
		// Remove unwanted columns from list screen
		add_filter( 'manage_edit-'. $this->get_post_type() .'_columns', array( $this, 'remove_unwanted_post_columns' ), 30 );
		
		// Remove from Yoast sitemap if $this->exclude_from_sitemap is true
		add_filter( 'wpseo_sitemap_exclude_post_type', array( $this, 'yoast_exclude_from_sitemap' ), 10, 2 );
		
	}
	
	/**
	 * Get post type
	 *
	 * @return null
	 */
	public function get_post_type() {
		return $this->post_type;
	}
	
	
	/**
	 * Return args used to register the post type.
	 * Custom post types extending this class should overwrite this and add any custom args on top of the default ones.
	 *
	 * @return array
	 */
	public function get_post_type_args() {
		return $this->post_type_args;
	}
	
	
	/**
	 * Return a single arg from the post type args.
	 *
	 * @param string      $key
	 * @param string|null $sub_key
	 *
	 * @return mixed
	 */
	public function get_post_type_arg( $key, $sub_key = null ) {
		if ( isset($this->post_type_args[$key]) ) {
			if ( $sub_key ) {
				if ( isset($this->post_type_args[$key][$sub_key]) ) {
					return $this->post_type_args[$key][$sub_key];
				}
			}else{
				return $this->post_type_args[$key];
			}
		}
		return null;
	}
	
	
	/**
	 * Register the post type.
	 * Custom post types should override get_post_type_args to customize any args.
	 * Applies to:
	 *  1. Assessments
	 *  2. Teams
	 *  3. Entries
	 *  4. Sessions
	 *
	 * @return void
	 */
	public function register_custom_post_type() {
		$post_type = $this->get_post_type();
		$args = $this->get_post_type_args();
		
		register_post_type( $post_type, $args );
	}
	
	/**
	 * Customize the "Add title" placeholder text
	 *
	 * @param $default
	 * @param $post
	 *
	 * @return mixed
	 */
	public function customize_title_placeholder( $default, $post ) {
		if ( $post->post_type == $this->get_post_type() ) {
			$text = $this->get_post_type_arg( 'labels', 'enter_title_here' );
			if ( $text ) return $text;
		}
		
		return $default;
	}
	
	/**
	 * Remove unwanted meta boxes from edit screen
	 *
	 * @return void
	 */
	public function remove_unwanted_meta_boxes() {
		remove_meta_box('wpseo_meta', $this->get_post_type(), 'normal');
		remove_meta_box('edit-box-ppr', $this->get_post_type(), 'normal');
		remove_meta_box('evoia_mb', $this->get_post_type(), 'normal');
		
		// Move author div to side
		remove_meta_box( 'authordiv', $this->get_post_type(), 'normal' );
		add_meta_box( 'authordiv', 'Author', 'post_author_meta_box', $this->get_post_type(), 'side' );
		
		// Remove slug box, use permalink if needed
		remove_meta_box( 'slugdiv', $this->get_post_type(), 'normal' );
		
		// Remove the "Post Type Attributes" meta box
		remove_meta_box( 'pageparentdiv', $this->get_post_type(), 'side' );
		
		// Remove the "Revisions" meta box (but keep revision functionality)
		remove_meta_box( 'revisionsdiv', $this->get_post_type(), 'normal' );
	}
	
	
	/**
	 * Remove unwanted columns from list screen
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function remove_unwanted_post_columns( $columns ) {
		unset( $columns['new_post_thumb'] );
		unset( $columns['wpseo-score'] );
		unset( $columns['wpseo-title'] );
		unset( $columns['wpseo-metadesc'] );
		unset( $columns['wpseo-focuskw'] );
		unset( $columns['wpseo-links'] );
		unset( $columns['wpseo-linked'] );
		
		// Move to end of list
		$date = $columns['date'];
		unset( $columns['date'] );
		$columns['date'] = $date;
		
		return $columns;
	}
	
	/**
	 * Remove from Yoast sitemap, if $this->exclude_from_sitemap is true
	 *
	 * @param bool $is_excluded
	 * @param string $post_type
	 *
	 * @return bool
	 */
	public function yoast_exclude_from_sitemap( $is_excluded, $post_type ) {
		if ( $post_type == $this->get_post_type() && $this->exclude_from_sitemap ) {
			return true;
		}
		
		return $is_excluded;
	}
	
}