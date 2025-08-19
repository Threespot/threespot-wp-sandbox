<?php

namespace MapSVG;

use Error;

class PostEditorMapLoader
{

    private $mappablePostTypes;

    public static function getTableName($postType)
    {
        return 'posts_' . str_replace('-', '_', $postType);
    }


    public function __construct()
    {
        $this->mappablePostTypes = \MapSVG\Options::get('mappable_post_types') ?? [];
    }

    function init()
    {

        $this->addLocationFieldToPosts();

        if (is_admin()) {
            add_action('admin_init', array($this, 'setupTinymce'));
            add_action('add_meta_boxes', array($this, 'addLocationMetaBox'));
            add_action('save_post', array($this, 'saveLocationMeta'));
            add_action('admin_enqueue_scripts', array($this, 'addScriptsForPostEditor'));
            if (!empty($this->mappablePostTypes)) {
                foreach ($this->mappablePostTypes as $postType) {
                    add_action('untrash_' . $postType, [$this, 'updatePostData']);
                    add_action('wp_trash_' . $postType, [$this, 'deletePostData']);
                }
            }
        }

        if (!empty($this->mappablePostTypes)) {
            foreach ($this->mappablePostTypes as $postType) {
                add_action('rest_after_insert_' . $postType, [$this, 'updatePostData'], 10, 2);
            }
        }
    }

    function updatePostData($postOrId)
    {

        if (!is_object($postOrId)) {
            $post = get_post($postOrId);
        } else {
            $post = $postOrId;
        }

        $id = $post->ID;


        $table = static::getTableName($post->post_type);

        $schemaRepo = RepositoryFactory::get("schema");
        $schema = $schemaRepo->findOne(["name" => $table]);

        if (!$schema) {
            return false;
        }

        $meta = get_post_meta($id, 'mapsvg_location', true);
        $location = json_decode($meta, true);

        $params = array(
            'post'  => $id,
            'location' => $location
        );

        if (!$location || !$location['geoPoint']) {
            $this->deletePostData($id);
            return;
        }
        $postsRepo = RepositoryFactory::get($table);

        if (!$postsRepo) {
            Logger::error("MapSVG: trying to update location meta field of the post type that is not connected to any map: " . $post->post_type);
        } else {
            $post = $postsRepo->findOne(["post" => $id]);


            if ($post) {
                $post->update($params);
                $postsRepo->update($post);
            } else {
                $postsRepo->create($params);
            }
        }

        // 
    }

    function deletePostData($postID)
    {
        $post = get_post($postID);
        $table = static::getTableName($post->post_type);
        $postsRepo = RepositoryFactory::get($table);
        if ($postsRepo) {
            $postInMapsvgTable = $postsRepo->findOne(["post" =>  $postID]);
            if ($postInMapsvgTable) {
                $postsRepo->delete($postInMapsvgTable->id);
            }
        }
    }

    function addLocationFieldToPosts()
    {
        if (!empty($this->mappablePostTypes)) {
            foreach ($this->mappablePostTypes as $postType) {
                add_post_type_support($postType, 'custom-fields');
                register_meta('post', 'mapsvg_location', array(
                    'object_subtype' => $postType,
                    'show_in_rest' => true,
                    'type' => 'string',
                    'single' => true,
                ));
            }
        }
    }

    // Register meta box
    public function addLocationMetaBox()
    {
        if (!empty($this->mappablePostTypes)) {
            foreach ($this->mappablePostTypes as $postType) {
                if (!use_block_editor_for_post_type($postType)) {
                    add_meta_box(
                        'mapsvg_location_meta_box',
                        'Location @mapsvg',
                        [$this, 'locationMetaBoxCallback'],
                        $postType, // Change this to target specific post types
                        'normal',
                        'high'
                    );
                }
            }
        }
    }



    /**
     * Add buttons to Visual Editor
     */
    public function setupTinymce()
    {
        // Check if the logged in WordPress User can edit Posts or Pages
        // If not, don't register our TinyMCE plugin
        if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
            return;
        }

        // Check if the logged in WordPress User has the Visual Editor enabled
        // If not, don't register our TinyMCE plugin
        if (get_user_option('rich_editing') !== 'true') {
            return;
        }

        wp_register_style('mapsvg-tinymce', MAPSVG_PLUGIN_URL . "css/mapsvg-tinymce.css", null, MAPSVG_ASSET_VERSION);
        wp_enqueue_style('mapsvg-tinymce');

        add_filter('mce_external_plugins', array($this, 'addTinymceJs'));
        add_filter('mce_buttons', array($this, 'addTinymceButton'));
    }

    /**
     * Adds a TinyMCE plugin compatible JS file to the TinyMCE / Visual Editor instance
     *
     * @param array $plugin_array Array of registered TinyMCE Plugins
     * @return array Modified array of registered TinyMCE Plugins
     */
    public function addTinymceJs($plugin_array)
    {
        $plugin_array['mapsvg'] = MAPSVG_PLUGIN_URL . 'js/mapsvg-admin/wp-classic-editor/tinymce-mapsvg.js';
        return $plugin_array;
    }

    /**
     * Adds a button to the TinyMCE / Visual Editor which the user can click
     * to insert a custom CSS class.
     *
     * @param array $buttons Array of registered TinyMCE Buttons
     * @return array Modified array of registered TinyMCE Buttons
     */
    public function addTinymceButton($buttons)
    {
        array_push($buttons, 'mapsvg');
        return $buttons;
    }

    function addScriptsForPostEditor($hook_suffix)
    {
        global $post;
        $post_types = Options::get('mappable_post_types') ?? [];
        $screen = get_current_screen();
        if (empty($post_types)) {
            return;
        }

        $onMappablePostTypeEditorScreen = is_object($screen) && in_array($screen->post_type, $post_types) && in_array($hook_suffix, array('post.php', 'post-new.php'));

        if ($onMappablePostTypeEditorScreen) {

            $itsGutenbergEnabledForThePost = use_block_editor_for_post($post);

            if ($itsGutenbergEnabledForThePost) {
                static::addJsCssGutenberg();
            } else {
                static::addJsCssClassicEditor();
            }
        }
    }

    public function locationMetaBoxCallback($post)
    {
        // Retrieve current value
        $location = get_post_meta($post->ID, 'mapsvg_location', true);
        echo '<div id="mapsvg-classic-editor-container">';
        echo '    <div id="mapsvg"></div>';
        echo '    <div id="mapsvg-filters"></div>';
        echo '</div>';
        echo '<input type="hidden" id="mapsvg_location" name="mapsvg_location" value="' . esc_attr($location) . '">';
    }

    public function saveLocationMeta($post_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        if (isset($_POST['mapsvg_location'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
            $location = wp_json_encode(json_decode(wp_unslash($_POST['mapsvg_location']))); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing
            update_post_meta($post_id, 'mapsvg_location', $location);
            $this->updatePostData($post_id);
        }
    }

    public function addJsCssCommon()
    {
        // Add common JS and CSS files
        wp_enqueue_script('mapsvg', MAPSVG_PLUGIN_URL . 'js/mapsvg.min.js', array('jquery'), MAPSVG_VERSION, true);
        wp_enqueue_style('mapsvg', MAPSVG_PLUGIN_URL . 'css/mapsvg.css', array(), MAPSVG_VERSION);

        // Add jQuery Growl for notifications
        wp_enqueue_script('jquery-growl', MAPSVG_PLUGIN_URL . 'js/vendor/jquery-growl/jquery.growl.js', array('jquery'), "1.3.1", true);
        wp_enqueue_style('jquery-growl', MAPSVG_PLUGIN_URL . 'js/vendor/jquery-growl/jquery.growl.css', array(), "1.3.1");

        wp_enqueue_style(
            'mapsvg-gutenberg-css',
            MAPSVG_PLUGIN_URL . 'css/mapsvg-gutenberg.css',
            array(),
            MAPSVG_ASSET_VERSION
        );
    }

    /**
     * Adds scripts and CSS to Gutenberg editor
     */
    function addJsCssClassicEditor()
    {
        global $post;
        \MapSVG\Front::addJsCss();

        $this->addJsCssCommon();

        wp_enqueue_script('mapsvg-classic-editor', MAPSVG_PLUGIN_URL . 'js/mapsvg-admin/wp-classic-editor/mapsvg-classic-editor.js', ['jquery', 'mapsvg'], MAPSVG_VERSION, true);

        $markersRepo = new MarkersRepository();
        $markerImages = $markersRepo->find();
        $apiKey = Options::get("google_api_key");

        // Localize script with necessary data
        wp_localize_script('mapsvg-classic-editor', 'mapsvgBackendParams', [
            "markerImages" => $markerImages,
            "googleApiKey" => $apiKey,
            "mapsPath" => MAPSVG_MAPS_URL,
            'post' => $post->ID,
            "mapsvg_location" => get_post_meta($post->ID, 'mapsvg_location', true),
        ]);
    }

    /**
     * Adds scripts and CSS to Gutenberg editor
     */
    function addJsCssGutenberg()
    {
        \MapSVG\Front::addJsCss();

        $this->addJsCssCommon();

        wp_register_script(
            'mapsvg-gutenberg-sidebar',
            MAPSVG_PLUGIN_URL . 'dist/mapsvg-gutenberg.build.js',
            array('wp-blocks', 'wp-plugins', 'wp-edit-post', 'wp-i18n', 'wp-element'),
            MAPSVG_ASSET_VERSION,
            true
        );

        $markersRepo = new MarkersRepository();
        $markerImages = $markersRepo->find();
        $apiKey = Options::get("google_api_key");

        wp_localize_script('mapsvg-gutenberg-sidebar', 'mapsvgBackendParams', array(
            "markerImages" => $markerImages,
            "googleApiKey" => $apiKey,
            "mapsPath" => MAPSVG_MAPS_URL
        ));

        wp_enqueue_script('mapsvg-gutenberg-sidebar');
    }
}
