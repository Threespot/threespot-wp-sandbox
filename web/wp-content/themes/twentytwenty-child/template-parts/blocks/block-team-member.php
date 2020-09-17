<?php
/**
 * Team Member block
 * https://www.billerickson.net/building-gutenberg-block-acf/
 * @package      ClientName
 * @author       Bill Erickson
 * @since        1.0.0
 * @license      GPL-2.0+
**/

$name = get_field( 'name' );
$title = get_field( 'title' );
$photo = get_field( 'photo' );


echo '<div class="team-member">';
	echo '<div class="team-member--header">';
		if( !empty( $photo ) )
			echo wp_get_attachment_image( $photo['ID'], 'thumbnail', null, array( 'class' => 'team-member--avatar' ) );
		if( !empty( $name ) )
			echo '<h4>' . esc_html( $name ) . '</h4>';
		if( !empty( $title ) )
			echo '<h6 class="alt">' . esc_html( $title ) . '</h6>';
	echo '</div>';
    echo '<div class="team-member--innerBlocks">';
    echo '<InnerBlocks />';
    echo '</div>';
echo '</div>';

