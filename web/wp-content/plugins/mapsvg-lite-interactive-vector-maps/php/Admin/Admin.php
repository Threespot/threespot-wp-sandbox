<?php

/**
 * Created by PhpStorm.
 * User: Roma
 * Date: 24.10.18
 * Time: 9:59
 */

namespace MapSVG;

class Admin
{
    public function __construct()
    {
        $this->addHooks();
    }

    private function addHooks()
    {
        add_action('admin_menu', array($this, 'addWpAdminMenuItem'));
        add_action('admin_enqueue_scripts', '\MapSVG\Admin::addJsCss', 0);
    }

    /**
     * Add menu element to WP Admin menu
     */
    function addWpAdminMenuItem()
    {
        global $mapsvg_settings_page;

        if (function_exists('add_menu_page') && current_user_can('edit_posts'))
            $mapsvg_settings_page = add_menu_page(
                'MapSVG',
                'MapSVG',
                'edit_posts',
                'mapsvg-config',
                array($this, 'renderAdminPage'),
                'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDI3LjUuMCwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPgo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IgoJIHZpZXdCb3g9IjAgMCAxOTIgMTkyIiBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCAxOTIgMTkyOyIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+CjxwYXRoIGQ9Ik05NS4yLDAuMkM0Mi41LDAuMiwwLjEsNDMuNCwwLjEsOTYuMVM0My40LDE5Miw5Ni4xLDE5MlMxOTIsMTQ4LjgsMTkyLDk2LjFTMTQ3LjksMC4yLDk1LjIsMC4yeiBNMTU4LjMsMTM5LjNIMzMKCWMtNS4yLDAtOC42LTYtNi0xMC40TDkwLDIwYzIuNi00LjMsOS41LTQuMywxMi4xLDBsNjMuMSwxMDguOUMxNjYuOSwxMzMuMiwxNjMuNSwxMzkuMywxNTguMywxMzkuM3oiLz4KPC9zdmc+Cg==',
                66
            );
    }


    /**
     * Add admin JS & CSS
     */
    static function addJsCss($hook_suffix)
    {

        global $mapsvg_settings_page;

        // Load scripts only if it's MapSVG config page! Don't load scripts on all WP Admin pages
        if ($mapsvg_settings_page != $hook_suffix)
            return;

        if (isset($_GET['page']) && $_GET['page'] == 'mapsvg-config') {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
            // Load scripts and CSS for WP Media file uploader
            wp_enqueue_media();


            wp_register_style('bs.css', MAPSVG_PLUGIN_URL . 'js/vendor/bootstrap/bootstrap.min.css', null, '5.2',);
            wp_enqueue_style('bs.css');
            wp_register_style('main.css', MAPSVG_PLUGIN_URL . 'css/mapsvg-admin.css', null, MAPSVG_ASSET_VERSION,);
            wp_enqueue_style('main.css');
        }

        // Load common JS/CSS files
        \MapSVG\Front::addJsCss();
    }

    /**
     * Render MapSVG settings page in WP Admin
     */
    function renderAdminPage()
    {

        // Check user rights
        if (!current_user_can('edit_posts'))
            die();

        if (isset($_GET['map_id']) && !empty($_GET['map_id'])) {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
            $this->edit($_REQUEST);  // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
        } else {
            $this->index($_REQUEST);  // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
        }

        

        return true;
    }

    public function index($request)
    {
        $templateData = array();
        $mapsRepo = RepositoryFactory::get("map");

        $mapsRepo->deleteAllRemovedOneDayAgo();

        // Load the list of available SVG files
        $svgRepo = new SVGFileRepository();

        $currentPage = isset($_GET['p']) ? intval($_GET['p']) : 1;  // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing

        // Load the list of created maps
        $query = new Query(array('perpage' => 30, 'page' => $currentPage, 'filters' => ['status' =>  1], 'fields' => array('id', 'title')));

        $post_types = $this->getPostTypes();

        $options = Options::getAll();

        // Get the current user's information
        $current_user = wp_get_current_user();

        // Get the user's display name
        $user_name = $current_user->display_name;

        // Get the user's email address
        $user_email = $current_user->user_email;

        $mapsData = $mapsRepo->find($query);

        // Function to generate link
        function generateLink($page)
        {
            $currentUrl = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
            $parsedUrl = wp_parse_url($currentUrl);
            parse_str($parsedUrl['query'], $params);
            $params['p'] = $page;
            $newQueryString = http_build_query($params);
            return $parsedUrl['path'] . '?' . $newQueryString;
        }

        // Generate previous and next links
        $prevLink = $currentPage > 1 ? generateLink($currentPage - 1) : '#';
        $nextLink = $mapsData["hasMore"] ? generateLink($currentPage + 1) : '#';


        $templateData = array(
            'user' => array("name" => $user_name, "email" => $user_email),
            'page' => 'index',
            'maps' => $mapsData["items"],
            'gitBranch' => "",
            'svgFiles' => $svgRepo->find(),
            'options' => $options,
            'postTypes' => $post_types,
            'userIsAdmin' => current_user_can("create_users") ? true : false,
            'pagination' => array(
                "page" => $currentPage,
                "hasMore" => $mapsData["hasMore"],
                'nextLink' => $nextLink,
                'prevLink' => $prevLink
            )
        );



        wp_register_script('admin.mapsvg', MAPSVG_PLUGIN_URL . 'js/mapsvg-admin/core/admin.js', array('jquery'), MAPSVG_ASSET_VERSION, true);
        wp_enqueue_script('admin.mapsvg');


        wp_localize_script('admin.mapsvg', 'mapsvgBackendParams', array(
            'page' => 'index',
            'user' => array(
                "name" => $user_name,
                "email" => $user_email,
                "isAdmin" => current_user_can("create_users")
            ),
            'maps' => $mapsRepo->find($query),
            'gitBranch' => "",
            'svgFiles' => $svgRepo->find(),
            'options' => $options,
            'postTypes' => $post_types,
            'userIsAdmin' => current_user_can("create_users") ? true : false
        ));

        $this->renderOutput('index', $templateData);
    }

    public function edit($request)
    {
        $templateData = array();

        $mapsRepo = RepositoryFactory::get("map");


        $map = $mapsRepo->findById($request['map_id']);
        if (!$map) {
            echo ("Map does not exists");
            return;
        }

        if (empty($map->title)) {
            $map->setTitle('New map');
        }

        $markersRepo = new MarkersRepository();
        $markerImages = $markersRepo->find();

        $db = Database::get();

        $fullTextMinWord = $db->get_row("show variables like 'ft_min_word_len'", OBJECT);
        $fullTextMinWord = $fullTextMinWord ? $fullTextMinWord->Value : 0;

        $options = Options::getAll();

        $post_types = $this->getPostTypes();

        // Load the list of available SVG files
        $svgRepo = new SVGFileRepository();

        // Get the current user
        $current_user = wp_get_current_user();

        // Get the user's display name
        $user_name = $current_user->display_name;

        // Get the user's email address
        $user_email = $current_user->user_email;



        wp_register_script('admin.mapsvg', MAPSVG_PLUGIN_URL . 'js/mapsvg-admin/core/admin.js', array('jquery'), MAPSVG_ASSET_VERSION, true);
        wp_enqueue_script('admin.mapsvg');

        wp_localize_script('admin.mapsvg', 'mapsvgBackendParams', array(
            'page' => 'edit',
            'map' => array(
                "id" => $map->id,
                "svgFileLastChanged" => $map->svgFileLastChanged,
                "options" => array(
                    "regionPrefix" => isset($map->options["regionPrefix"]) ? $map->options["regionPrefix"] : "",
                    "loadingText" => isset($map->options["loadingText"]) ? $map->options["loadingText"] : "Loading map..."
                )
            ),
            'user' => array(
                "name" => $user_name,
                "email" => $user_email,
                "isAdmin" => current_user_can("create_users")
            ),
            'phpIni' => array(
                'post_max_size'       => ini_get('post_max_size'),
                'upload_max_filesize' => ini_get('upload_max_filesize')
            ),
            'gitBranch' => "",
            'markerImages' => $markerImages,
            'svgFiles' => $svgRepo->find(),
            'fullTextMinWord' => $fullTextMinWord,
            'options' => $options,
            'postTypes' => $post_types,
            'userIsAdmin' => current_user_can("create_users") ? true : false
        ));


        $templateData["map"] = $map;
        $this->renderOutput('edit', $templateData);
    }

    public function renderOutput($page, $data)
    {
        include(__DIR__ . DIRECTORY_SEPARATOR . ucfirst($page) . DIRECTORY_SEPARATOR . 'header.php');
        include(__DIR__ . DIRECTORY_SEPARATOR . ucfirst($page) . DIRECTORY_SEPARATOR . 'body.php');
        include(__DIR__ . DIRECTORY_SEPARATOR . ucfirst($page) . DIRECTORY_SEPARATOR . 'footer.php');
        // START support
        include(__DIR__ . DIRECTORY_SEPARATOR . "Common" . DIRECTORY_SEPARATOR . 'support_modal.php');
        // END
    }


    function getPostTypes()
    {
        global $wpdb;

        $args = array(
            '_builtin' => false
        );

        $_post_types = get_post_types($args, 'names');
        if (!$_post_types)
            $_post_types = array();

        $post_types = array();
        foreach ($_post_types as $pt) {
            if ($pt != 'mapsvg')
                $post_types[] = $pt;
        }
        $post_types[] = 'post';
        $post_types[] = 'page';
        return $post_types;
    }
}
