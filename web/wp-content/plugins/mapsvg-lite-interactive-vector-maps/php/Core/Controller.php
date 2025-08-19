<?php

namespace MapSVG;

/**
 * Core Controller class used to implement actual controllers.
 * @package MapSVG
 */
class Controller
{

	/**
	 * Renders a response.
	 *
	 * @param mixed $data
	 * @param int $status
	 * @param string $output
	 * @param string $template
	 *
	 * @return \WP_REST_Response|void
	 */
	public static function render($data, $status = 200, $output = 'json', $template = '')
	{
		if ($output === 'html' && $template) {
			$reflector = new \ReflectionClass(get_called_class());
			$filename = $reflector->getFileName();
			$dir = dirname($filename);

			$templatePath = $dir . '/templates/' . $template . '.php';



			if (file_exists($templatePath)) {
				extract($data);
				include $templatePath;
			} else {
				echo '<!DOCTYPE html><html><head><title>Error</title></head><body>';
				echo '<p>Template not found: ' . esc_html($template) . '</p>';
				echo '</body></html>';
			}
		} else {

			return new \WP_REST_Response(json_decode(wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), $status);
		}
	}
}
