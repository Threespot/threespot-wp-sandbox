<?php
 
 //load styles from parent theme
 add_action( 'wp_enqueue_scripts', 'enqueue_parent_styles' );

function enqueue_parent_styles() {
   wp_enqueue_style( 'parent-style', get_template_directory_uri().'/style.css' );
}

/**
 * Register Blocks
 * @link https://www.billerickson.net/building-gutenberg-block-acf/#register-block
 *
 */
function be_register_blocks() {
	
	if( ! function_exists( 'acf_register_block_type' ) )
		return;

	acf_register_block_type( array(
		'name'			=> 'team-member',
		'title'			=> __( 'Team Member', 'clientname' ),
		'render_template'	=> 'template-parts/blocks/block-team-member.php',
		'category'		=> 'formatting',
		'icon'			=> 'admin-users',
		'mode'			=> 'preview',
        'keywords'		=> array( 'profile', 'user', 'author' ),
        'supports'		=> [
            'align'			=> false,
            'anchor'		=> true,
            'customClassName'	=> true,
            'jsx' 			=> true,
        ]
	));

}
add_action('acf/init', 'be_register_blocks' );




