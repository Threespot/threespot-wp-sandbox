<?php
/**
 *  Mailhog SMTP server setup
 */
namespace App;
use function Env\env;

if ($_ENV['PANTHEON_ENVIRONMENT'] == 'lando') {
	add_action('phpmailer_init', function($phpmailer) {
		$phpmailer->isSMTP();
		$phpmailer->Host = 'mailhog';
		$phpmailer->SMTPAuth = false;
		$phpmailer->Port = 1025;
	});
}
