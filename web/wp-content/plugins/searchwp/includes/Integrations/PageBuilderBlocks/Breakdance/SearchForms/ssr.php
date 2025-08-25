<?php
use SearchWP\Integrations\PageBuilderBlocks;

// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
$form_id = isset( $propertiesData['content']['searchwp_form']['form_id'] ) ?
	absint( $propertiesData['content']['searchwp_form']['form_id'] ) :
	0;

$form = \SearchWP\Forms\Storage::get( $form_id );

if ( ! empty( $form ) ) {
	echo \SearchWP\Forms\Frontend::render( [ 'id' => $form_id ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	wp_enqueue_style( 'searchwp-forms' );
	wp_enqueue_script( 'searchwp-forms' );
} else {
	if ( ! isset( $_REQUEST['action'] ) || $_REQUEST['action'] !== 'breakdance_server_side_render' ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return '<div></div>';
	}
	echo PageBuilderBlocks::get_component_preview( PageBuilderBlocks::get_text( 'select_form_from_settings' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
