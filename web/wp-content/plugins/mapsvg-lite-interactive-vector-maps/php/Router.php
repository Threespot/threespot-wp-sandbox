<?php

/**
 * Created by PhpStorm.
 * User: Roma
 * Date: 24.10.18
 * Time: 10:52
 */

namespace MapSVG;

/**
 * Router class that registers routes for WP Rest API
 * and also does DB upgrades
 */
class Router
{

	public function __construct()
	{
		$this->run();
	}

	public function run()
	{

		// This should be called before "rest_api_init"
		add_action('init', array($this, 'setupGutenberg'));

		

		add_action('rest_api_init', function () {

			$this->registerMethodCheckRoutes();

			

			$this->registerMapRoutes();
			$this->registerMapV2Routes();

			$this->registerSchemaRoutes();

			$this->registerRegionRoutes();
			$this->registerObjectRoutes();

			$this->registerPostRoutes();

			$this->registerGeocodingRoutes();

			

			$this->registerGoogleApiRoutes();
			$this->registerSvgFileRoutes();
			$this->registerShortcodesRoutes();
			$this->registerMarkerFileRoutes();
			
			$this->registerOptionsRoutes();

			

			


		});

		add_filter('rest_pre_serve_request', array($this, 'add_nocache_headers'), 11, 4);

		// add_action('send_headers', array($this, 'finish_loggeer'), 10);
	}

		

	// function finish_loggeer($served)
	// {
	// 	Logger::sendHeaders();
	// }

	function add_nocache_headers($served, $response, $request, $server)
	{

		if (is_a($response, 'WP_REST_Response') && strpos($request->get_route(), 'mapsvg/v') !== false) {
			nocache_headers();
		}

		

		return $served;
	}


	public static function methodCheck()
	{
		return new \WP_REST_Response(array('message' => 'Method check is available'), 200);
	}

	public function registerMethodCheckRoutes()
	{
		$baseRoute = '/method-check/';
		register_rest_route('mapsvg/v1', $baseRoute, array(
			array(
				'methods' => 'PUT',
				'callback' => '\MapSVG\Router::methodCheck',
				'permission_callback' => function () {
					return true;
				}
			),
			array(
				'methods' => 'DELETE',
				'callback' => '\MapSVG\Router::methodCheck',
				'permission_callback' => function () {
					return true;
				}
			)
		));
	}

	

	

	

	

	function setupGutenberg()
	{
		$postEditorMapLoader = new PostEditorMapLoader();
		$postEditorMapLoader->init();
	}


	
	public function registerOptionsRoutes()
	{
		$baseRoute = '/options/';
		register_rest_route('mapsvg/v1', $baseRoute, array(
			array(
				'methods' => 'POST',
				'callback' => '\MapSVG\OptionsController::update',
				'permission_callback' => function () {
					return current_user_can('edit_posts');
				}
			)
		));
		register_rest_route('mapsvg/v1', $baseRoute . 'access', array(
			array(
				'methods' => 'POST',
				'callback' => '\MapSVG\OptionsController::setAccess',
				'permission_callback' => function () {
					return current_user_can('edit_posts');
				}
			)
		));
	}

	public function registerMapRoutes()
	{
		$baseRoute = '/maps/';
		register_rest_route('mapsvg/v1', $baseRoute, array(
			array(
				'methods' => 'GET',
				'callback' => '\MapSVG\MapController::index',
				'permission_callback' => function () {
					return true;
				}
			)
		));
		register_rest_route('mapsvg/v1', $baseRoute . '(?P<id>\d+)', array(
			array(
				'methods' => 'GET',
				'callback' => '\MapSVG\MapController::get',
				'permission_callback' => function () {
					return true;
				}
			)
		));
		$routeAdded = register_rest_route('mapsvg/v1', $baseRoute . '(?P<id>\d+)/svg', array(
			array(
				'methods' => 'GET',
				'callback' => '\MapSVG\MapController::getSvg',
				'permission_callback' => function () {
					return true;
				}
			)
		));
		register_rest_route('mapsvg/v1', $baseRoute . '(?P<id>\d+)/copy', array(
			array(
				'methods' => 'POST',
				'callback' => '\MapSVG\MapController::copy',
				'permission_callback' => function () {
					return current_user_can('edit_posts');
				}
			)
		));
		register_rest_route('mapsvg/v1', $baseRoute . '(?P<id>\d+)', array(
			array(
				'methods' => 'PUT',
				'callback' => '\MapSVG\MapController::update',
				'permission_callback' => function () {
					return current_user_can('edit_posts');
				}
			)
		));
		register_rest_route('mapsvg/v1', $baseRoute . '(?P<id>\d+)', array(
			array(
				'methods' => 'DELETE',
				'callback' => '\MapSVG\MapController::delete',
				'permission_callback' => function () {
					return current_user_can('edit_posts');
				}
			)
		));
		register_rest_route('mapsvg/v1', $baseRoute . '/createFromV2', array(
			array(
				'methods' => 'POST',
				'callback' => '\MapSVG\MapController::createFromV2',
				'permission_callback' => function () {
					return current_user_can('edit_posts');
				}
			)
		));
		register_rest_route('mapsvg/v1', $baseRoute, array(
			array(
				'methods' => 'POST',
				'callback' => '\MapSVG\MapController::create',
				'permission_callback' => function () {
					return current_user_can('edit_posts');
				}
			)
		));
	}

	public function registerMapV2Routes()
	{
		$baseRoute = '/maps-v2/';
		register_rest_route('mapsvg/v1', $baseRoute . '(?P<id>\d+)', array(
			array(
				'methods' => 'GET',
				'callback' => '\MapSVG\MapV2Controller::get',
				'permission_callback' => function () {
					return true;
				}
			)
		));
	}

	public function registerPostRoutes()
	{
		register_rest_route('mapsvg/v1', '/posts', array(
			array(
				'methods' => 'GET',
				'callback' => '\MapSVG\PostController::index',
				'permission_callback' => function () {
					return true;
				}
			)
		));
	}

	public function registerRegionRoutes()
	{
		$baseRoute = '/regions/(?P<_collection_name>[a-zA-Z0-9-_]+)';
		register_rest_route('mapsvg/v1', $baseRoute, array(
			array(
				'methods' => 'GET',
				'callback' => '\MapSVG\RegionsController::index',
				'permission_callback' => function () {
					return true;
				}
			)
		));
		register_rest_route('mapsvg/v1', $baseRoute . '/(?P<id>.+)', array(
			array(
				'methods' => 'GET',
				'callback' => '\MapSVG\RegionsController::get',
				'permission_callback' => function () {
					return true;
				}
			)
		));
		register_rest_route('mapsvg/v1', $baseRoute . '/(?P<id>.+)', array(
			array(
				'methods' => 'PUT',
				'callback' => '\MapSVG\RegionsController::update',
				'permission_callback' => function () {
					return current_user_can('edit_posts');
				}
			)
		));
		register_rest_route('mapsvg/v1', $baseRoute . '/(?P<id>.+)', array(
			array(
				'methods' => 'DELETE',
				'callback' => '\MapSVG\RegionsController::delete',
				'permission_callback' => function () {
					return current_user_can('edit_posts');
				}
			)
		));
		register_rest_route('mapsvg/v1', $baseRoute, array(
			array(
				'methods' => 'POST',
				'callback' => '\MapSVG\RegionsController::create',
				'permission_callback' => function () {
					return current_user_can('edit_posts');
				}
			)
		));
		register_rest_route('mapsvg/v1', $baseRoute . '/import', array(
			array(
				'methods' => 'POST',
				'callback' => '\MapSVG\RegionsController::import',
				'permission_callback' => function () {
					return current_user_can('edit_posts');
				}
			)
		));
	}

	public function registerObjectRoutes()
	{
		$baseRoute = '/objects/(?P<_collection_name>[a-zA-Z0-9-_]+)';
		register_rest_route('mapsvg/v1', $baseRoute, array(
			array(
				'methods' => 'GET',
				'callback' => '\MapSVG\ObjectsController::index',
				'permission_callback' => function () {
					return true;
				}
			)
		));
		register_rest_route('mapsvg/v1', $baseRoute . '/(?P<id>.+)', array(
			array(
				'methods' => 'GET',
				'callback' => '\MapSVG\ObjectsController::get',
				'permission_callback' => function () {
					return true;
				}
			)
		));
		register_rest_route('mapsvg/v1', $baseRoute . '/(?P<id>.+)', array(
			array(
				'methods' => 'PUT',
				'callback' => '\MapSVG\ObjectsController::update',
				'permission_callback' => function () {
					return current_user_can('edit_posts');
				}
			)
		));
		register_rest_route('mapsvg/v1', $baseRoute . '/(?P<id>.+)', array(
			array(
				'methods' => 'DELETE',
				'callback' => '\MapSVG\ObjectsController::delete',
				'permission_callback' => function () {
					return current_user_can('edit_posts');
				}
			)
		));
		register_rest_route('mapsvg/v1', $baseRoute, array(
			array(
				'methods' => 'DELETE',
				'callback' => '\MapSVG\ObjectsController::clear',
				'permission_callback' => function () {
					return current_user_can('edit_posts');
				}
			)
		));
		register_rest_route('mapsvg/v1', $baseRoute, array(
			array(
				'methods' => 'POST',
				'callback' => '\MapSVG\ObjectsController::create',
				'permission_callback' => function () {
					return current_user_can('edit_posts');
				}
			)
		));
		register_rest_route('mapsvg/v1', $baseRoute . '/import', array(
			array(
				'methods' => 'POST',
				'callback' => '\MapSVG\ObjectsController::import',
				'permission_callback' => function () {
					return current_user_can('edit_posts');
				}
			)
		));
	}

	public function registerGeocodingRoutes()
	{
		$baseRoute = '/geocoding';
		register_rest_route('mapsvg/v1', $baseRoute, array(
			array(
				'methods'  => 'GET',
				'callback' => '\MapSVG\GeocodingController::index',
				'permission_callback' => function () {
					return true;
				}
			)
		));
	}

	public function registerSchemaRoutes()
	{
		$baseRoute = '/schemas';
		register_rest_route('mapsvg/v1', $baseRoute, array(
			array(
				'methods' => 'GET',
				'callback' => '\MapSVG\SchemaController::index',
				'permission_callback' => function () {
					return true;
				}
			)
		));
		register_rest_route('mapsvg/v1', $baseRoute . '/(?P<id>.+)', array(
			array(
				'methods' => 'GET',
				'callback' => '\MapSVG\SchemaController::get',
				'permission_callback' => function () {
					return true;
				}
			)
		));
		register_rest_route('mapsvg/v1', $baseRoute . '/(?P<id>.+)', array(
			array(
				'methods' => 'PUT',
				'callback' => '\MapSVG\SchemaController::update',
				'permission_callback' => function () {
					return current_user_can('edit_posts');
				}
			)
		));
		register_rest_route('mapsvg/v1', $baseRoute . '/(?P<id>.+)', array(
			array(
				'methods' => 'DELETE',
				'callback' => '\MapSVG\SchemaController::delete',
				'permission_callback' => function () {
					return current_user_can('edit_posts');
				}
			)
		));
		register_rest_route('mapsvg/v1', $baseRoute, array(
			array(
				'methods' => 'POST',
				'callback' => '\MapSVG\SchemaController::create',
				'permission_callback' => function () {
					return current_user_can('edit_posts');
				}
			)
		));
	}

	

	public function registerGoogleApiRoutes()
	{
		$baseRoute = '/googleapikeys';
		register_rest_route('mapsvg/v1', $baseRoute, array(
			array(
				'methods' => 'PUT',
				'callback' => '\MapSVG\GoogleApiKeysController::update',
				'permission_callback' => function () {
					return current_user_can('edit_posts');
				}
			)
		));
	}

	public function registerShortcodesRoutes()
	{
		$baseRoute = '/shortcodes';
		register_rest_route('mapsvg/v1', $baseRoute . '/(?P<shortcode>.+)', array(
			array(
				'methods' => 'GET',
				'callback' => '\MapSVG\ShortcodesController::get',
				'permission_callback' => function () {
					return true;
				}
			)
		));
	}

	public function registerSvgFileRoutes()
	{
		$baseRoute = '/svgfile';
		register_rest_route('mapsvg/v1', $baseRoute . '/download', array(
			array(
				'methods' => 'GET',
				'callback' => '\MapSVG\SVGFileController::download',
				'permission_callback' => function () {
					return true;
				}
			)
		));
		register_rest_route('mapsvg/v1', $baseRoute, array(
			array(
				'methods' => 'POST',
				'callback' => '\MapSVG\SVGFileController::create',
				'permission_callback' => function () {
					return current_user_can('edit_posts');
				}
			)
		));
		register_rest_route('mapsvg/v1', $baseRoute . '/update', array(
			array(
				'methods' => 'POST',
				'callback' => '\MapSVG\SVGFileController::update',
				'permission_callback' => function () {
					return current_user_can('edit_posts');
				}
			)
		));
		register_rest_route('mapsvg/v1', $baseRoute . '/copy', array(
			array(
				'methods' => 'POST',
				'callback' => '\MapSVG\SVGFileController::copy',
				'permission_callback' => function () {
					return current_user_can('edit_posts');
				}
			)
		));
		register_rest_route('mapsvg/v1', $baseRoute . '/reload', array(
			array(
				'methods' => 'POST',
				'callback' => '\MapSVG\SVGFileController::reload',
				'permission_callback' => function () {
					return current_user_can('edit_posts');
				}
			)
		));
	}

	public function registerMarkerFileRoutes()
	{
		$baseRoute = '/markers';
		register_rest_route('mapsvg/v1', $baseRoute, array(
			array(
				'methods' => 'POST',
				'callback' => '\MapSVG\MarkersController::create',
				'permission_callback' => function () {
					return current_user_can('edit_posts');
				}
			)
		));
	}

	

	
	
}
