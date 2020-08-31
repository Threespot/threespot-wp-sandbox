<?php

class R3D_Post_Type
{
  
  public static $instance;
  
  public $main;
  
  public function __construct()
  {
    
    $this->main = Real3DFlipbook::get_instance();
    
    $labels = array(
        'name'               => __( 'Real3D Flipbooks', 'r3dfb' ),
        'singular_name'      => __( 'Real3D Flipbook', 'r3dfb' ),
        'menu_name'          => __( 'Real3D Flipbook', 'r3dfb' ),
        'name_admin_bar'     => __( 'Real3D Flipbook', 'r3dfb' ),
        'add_new'            => __( 'Add New', 'r3dfb' ),
        'add_new_item'       => __( 'Add New Flipbook', 'r3dfb' ),
        'new_item'           => __( 'New Flipbook', 'r3dfb' ),
        'edit_item'          => __( 'Edit Flipook', 'r3dfb' ),
        'view_item'          => __( 'View Flipook', 'r3dfb' ),
        'all_items'          => __( 'All Flipbooks', 'r3dfb' ),
        'search_items'       => __( 'Search Flipooks', 'r3dfb' ),
        'parent_item_colon'  => __( 'Parent Flipooks:', 'r3dfb' ),
        'not_found'          => __( 'No Flipbooks found.', 'r3dfb' ),
        'not_found_in_trash' => __( 'No Flipbooks found in Trash.', 'r3dfb' )
    );
    
    $args = array(
        'labels'             => $labels,
        'description'        => __( 'Description.', 'r3dfb' ),
        'public'             => false,  //this removes the permalink option
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => false,
        'query_var'          => true,
        'rewrite'            => false, //array('slug' => $this->base->slug),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'menu_icon'          => 'dashicons-book',
        'supports'           => array( 'title' )
    );
    
    register_post_type( 'r3d', $args );
    
    register_taxonomy( 'r3d_category', 'r3d', array(
        'hierarchical'      => true,
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => true,
        'rewrite'           => array( 'slug' => 'r3d_category' ),
    ) );
    
    if ( is_admin() ) {
      $this->init_admin();
    }
  }

  public function init_admin()
  {
    
    // Remove quick editing from the r3d post type row actions.
    add_filter( 'post_row_actions', array( $this, 'custom_actions' ), 10, 1 );
    
    add_filter( 'manage_r3d_posts_columns', array( $this, 'r3d_columns' ) );
    add_action( 'manage_r3d_posts_custom_column', array( $this, 'r3d_columns_content' ), 10, 2 );
    
    add_filter( 'manage_edit-r3d_category_columns', array( $this, 'r3d_cat_columns' ) );
    add_filter( 'manage_r3d_category_custom_column', array( $this, 'r3d_cat_columns_content' ), 10, 3 );

  }

  public function custom_actions( $actions )
  {
    if ( isset( get_current_screen()->post_type ) && 'r3d' == get_current_screen()->post_type ) {
      unset( $actions['inline hide-if-no-js'] );

      $actions['duplicate'] = '<a href="">Duplicate</a>';

      // trace($actions);

    }
    
    return $actions;
  }

  public function r3d_columns()
  {
    
    $columns = array(
        'cb'        => '<input type="checkbox" />',
        'cover' => __( 'Cover', 'r3dfb' ),
        'title'     => __( 'Title', 'r3dfb' ),
        'shortcode' => __( 'Shortcode', 'r3dfb' ),
        'date'      => __( 'Date', 'r3dfb' )
    );
    
    return $columns;
  }

  public function r3d_cat_columns( $defaults )
  {
    $defaults['shortcode'] = 'Shortcode';
    $defaults['cover'] = 'Cover';
    
    return $defaults;
  }
  
  public function r3d_columns_content( $column_name, $post_id )
  {
    $post_id = absint( $post_id );

    $id = get_post_meta($post_id, 'id', true);
    
    switch ( $column_name ) {
      case 'shortcode':
        echo '<code>[real3dflipbook id="' . $id . '"]</code>'  /*<div id="'. $id . '" class="button-secondary copy-shortcode">Copy</div>*/;
        break;

      case 'cover':
        $book = get_option('real3dflipbook_' . $id);
        $thumb = 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=';
        if(isset($book['lightboxThumbnailUrl']))
          $thumb = $book['lightboxThumbnailUrl'];          
        echo '<div class="thumb" style=";background-image:url('.$thumb.');"><a href="#" class="edit" name="'.$id.'"></a></div>';
        break;

    }
  }
  
  public function r3d_cat_columns_content( $c, $column_name, $term_id = "" )
  {
    
    return '<code>[real3dflipbook books="' . get_term( $term_id, 'r3d_category' )->slug . '" limit="-1"][/real3dflipbook]</code>';
    
  }

  public static function get_instance()
  {
    
    if ( !isset( self::$instance ) && !( self::$instance instanceof R3D_Post_Type ) ) {
      self::$instance = new R3D_Post_Type();
    }
    
    return self::$instance;
    
  }
}

// Load the post-type class.
$r3d_post_type = R3D_Post_Type::get_instance();

