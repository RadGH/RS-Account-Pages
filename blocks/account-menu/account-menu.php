<?php

/**
 * @global   array $block The block settings and attributes.
 * @global   string $content The block inner HTML (empty).
 * @global   bool $is_preview True during backend preview render.
 * @global   int $post_id The post ID the block is rendering content against.
 *           This is either the post ID currently being displayed inside a query loop,
 *           or the post ID of the post hosting this block.
 * @global   array $context The context provided to the block by the post or it's parent block.
 */

$atts = array('class' => array(
	'wp-block-account-menu',
	'wp-block-navigation__container',
));

// Get the menu from the ACF field in the block settings.
$m = get_field( 'menu', $block['id'] );

if ( ! $m || $m == 'automatic' ) {
	$menu = RS_Account_Pages_Menu::get_current_menu();
}else{
	$menu = RS_Account_Pages_Menu::get_menu_by_slug( $m );
}

// Get the layout from the ACF field in the block settings.
$layout = get_field( 'layout', $block['id'] );
$atts['class'][] = 'layout--' . $layout;

// If no menu is set, don't output anything, except on the block editor
if ( empty($menu) && ! RS_Account_Pages_Utility::is_block_editor() ) {
	return;
}

if ( $menu ) {
	$atts['class'][] = 'menu--' . $menu->get_menu_slug();
}

// Get menu items to be displayed
$menu_items = $menu->get_menu_items();

?>
<ul <?php RS_Account_Pages_Utility::output_block_attributes( $block, $atts ); ?>>

	<?php
	
	if ( empty($menu) || empty($menu_items) ) {
		
		// Show a message if no actions are set.
		?>
		<li><em>No menu available.</em></li>
		<?php
		
	}else{
		
		// Display the menu items
		
		foreach( $menu_items as $slug => $item ) {
			// Output the menu item
			$classes = $item['classes'];
			$url = $item['url'];
			$title = $item['title'];
			
			// Add custom class(es) to the menu item
			$classes[] = 'wp-block-navigation-item';
			
			?>
			<li class="<?php echo esc_attr(implode(' ', $classes)); ?>">
				<?php
				if ( $url ) echo '<a href="'. esc_attr( $url ) .'">';
				
				echo esc_html( $title );
				
				if ( $url ) echo '</a>';
				?>
			</li>
			<?php
		}
		
	}
	
	?>
	
	
</ul>