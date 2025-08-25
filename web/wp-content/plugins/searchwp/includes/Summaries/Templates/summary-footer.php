<?php
/**
 * Summary footer HTML template.
 *
 * @since 4.3.16
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

</td>
</tr>
<tr>
	<td align="<?php echo is_rtl() ? 'right' : 'left'; ?>" valign="top" class="footer">
		<?php
		printf(
			wp_kses( /* translators: %1$s - site URL, %2$s - link to the documentation. */
				__( 'This email was auto-generated and sent from %1$s. Learn <a href="%2$s">how to disable.</a>', 'searchwp' ),
				[
					'a' => [
						'href' => [],
					],
				]
			),
			'<a href="' . esc_url( home_url() ) . '">' . esc_html( wp_specialchars_decode( get_bloginfo( 'name' ) ) ) . '</a>',
			'https://searchwp.com/documentation/setup/email-summaries/?utm_source=WordPress&utm_medium=Email+Summaries+disable+link&utm_campaign=SearchWP&utm_content=how+to+disable'
		);
		?>
	</td>
	</tr>
</table>
</td>
</tr>
</table>
</div>
</td>
<td><!-- Deliberately empty to support consistent sizing and layout across multiple email clients. --></td>
</tr>
</table>
</body>
</html>
