<?php
// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
$template_id = isset( $propertiesData['content']['searchwp_results']['template_id'] ) ?
	absint( $propertiesData['content']['searchwp_results']['template_id'] ) :
	0;

$engine = isset( $propertiesData['content']['searchwp_results']['engine'] ) ?
	sanitize_text_field( $propertiesData['content']['searchwp_results']['engine'] ) :
	'';
// phpcs:enable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase

if ( ! isset( $_REQUEST['action'] ) || $_REQUEST['action'] !== 'breakdance_server_side_render' ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	echo \SearchWP\Templates\Frontend::render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		[
			'id'     => absint( $template_id ),
			'engine' => esc_attr( $engine ),
		]
	);
} else {
	?>
	<div class="searchwp-logo" style="width: 100%; text-align: center;">
		<img src="<?php echo esc_url( SEARCHWP_PLUGIN_URL . 'assets/images/logo.svg' ); ?>" alt="" style="margin: 20px auto;">
		<p><?php esc_html_e( 'Search results will appear here, based on the settings of the Search Form pointing to this page (see the Search Form\'s "Target Page" setting).', 'searchwp' ); ?></p>
	</div>
	<?php
}
?>
