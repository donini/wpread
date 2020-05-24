<?php

/*

Content-Type: application/json

{
  "uid": "urn:uuid:1335c695-cfb8-4ebb-abbd-80da344efa6b",
  "updateDate": "2016-05-23T22:34:51.0Z",
  "titleText": "Amazon Developer Blog, week in review May 23rd",
  "mainText": "",
  "streamUrl": "https://developer.amazon.com/public/community/blog/myaudiofile.mp3",
  "redirectionUrl": "https://developer.amazon.com/public/community/blog"
}

{
  "uid": "urn:uuid:1335c695-cfb8-4ebb-abbd-80da344efa6b",
  "updateDate": "2016-05-23T00:00:00.0Z",
  "titleText": "Amazon Developer Blog, week in review May 23rd",
  "mainText": "Meet Echosim. A new online community tool for developers that simulates the look and feel of an Amazon Echo.",
  "redirectionUrl": "https://developer.amazon.com/public/community/blog"
}

 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Invalid request.' );
}

if ( ! class_exists( 'Alexa_Reader' ) ) {

  class Alexa_Reader {

    /**
     * Load the class instance.
     *
     * @var class Instance.
     */
    private static $instance = null;

    /**
     * Set the plugin instance.
     */
    public static function get_instance() {
      if ( null === self::$instance ) {
        self::$instance = new self();
      }
      return self::$instance;
    }

    private function __construct() {
      add_action( 'init', [ $this, 'create_channel_taxonomy' ], 0 );
      add_action('admin_menu', [ $this, 'remove_sub_menus' ]);
      add_filter( 'query_vars', [ $this, 'add_query_vars_filter' ] );
      add_action( 'manage_posts_custom_column' , [ $this, 'custom_columns' ], 10, 2 );
      add_action( 'manage_posts_columns' , [ $this, 'add_language_columns' ], 10, 2 );
      add_filter('acf/settings/save_json', [ $this, 'acf_json_save_point' ] );
      add_filter('acf/settings/load_json',  [ $this, 'acf_json_load_point' ] );
    }

    /**
     * Get recent news object.
     */
    function  get_recent_feed( $language = null, $size = 5 ) {

      $args = array(
          'post_type'      => 'post',
          'post_status'    => 'publish',
          'posts_per_page' => $size,
          'orderby'        => 'date',
          'order'          => 'DESC',
      );
    
      if ( ! empty( $language ) ) {
        $args['meta_key']   = '';
        $args['meta_value'] = $language;
      }
    
      $feeds = new WP_Query( $args );
    
      return $feeds;

    }
    
    /**
     * Get recent news array.
     */
    function get_recent_feed_array( $language ) {

      $feed_data = array();
      $feeds     = self::get_recent_feed( $language );
    
      if ( $feeds->have_posts() ) {
    
        while ( $feeds->have_posts() ) {
    
            $feeds->the_post();
    
            $item            = array();
            $item_id         = wp_generate_uuid4();
            $item_title      = get_the_title();
            $item_date       = get_the_date('Y-m-d\TH:i:s');
            $item_content    = get_the_content();
            $item_audio_type = get_field('audio_type');
            $item_audio_file = get_field('audio_file');
            $item_audio_url  = get_field('audio_url');
            $item_url        = get_the_permalink();
            $item_author     = get_the_author();
            $item_channel    = wp_get_post_terms( get_the_ID(), 'channel', [ 'fields' => 'names' ] );
            
            if ( 'en_US' === $language ) {
              $by_line   = 'By %1$s%2$s. %3$s';
              $from_line = ' from %s';
            } elseif ( 'pt_BR' === $language ) {
              $by_line   = 'Por %1$s%2$s. %3$s';
              $from_line = ' de %s';
            }
    
            if ( is_array( $item_channel ) && ! empty( $item_channel ) ) {
              $item_channel = sprintf( __( $from_line, 'wpread' ), $item_channel[0] );
            } else {
              $item_channel = '';
            }
    
            $item['uid']            = $item_id;
            $item['updateDate']     = $item_date . '.0Z';
            $item['titleText']      = $item_title;
            $item['mainText']       = sprintf( __( $by_line, 'wpread' ), $item_author, $item_channel, $item_content);

            if ( 'file' === $item_audio_type ) {
              $item['streamUrl']    = $item_audio_file;
            }

            if ( 'url' === $item_audio_type ) {
              $item['streamUrl']    = $item_audio_url;
            }

            $item['redirectionUrl'] = $item_url;
    
            array_push( $feed_data, $item );
        }

      }

      return $feed_data;

    }
    
    /**
     * Get recent news json.
     */
    function get_recent_feed_json( $language ) {

      return json_encode( self::get_recent_feed_array( $language ) );

    }
    
    /**
     * Create custom taxonomy for channels.
     */
    function create_channel_taxonomy() {
    
      $labels = array(
        'name'                       => _x( 'Channels', 'channel general name' ),
        'singular_name'              => _x( 'Channel', 'channel singular name' ),
        'search_items'               =>  __( 'Search Channels' ),
        'popular_items'              => __( 'Popular Channels' ),
        'all_items'                  => __( 'All Channels' ),
        'parent_item'                => null,
        'parent_item_colon'          => null,
        'edit_item'                  => __( 'Edit Channel' ), 
        'update_item'                => __( 'Update Channel' ),
        'add_new_item'               => __( 'Add New Channel' ),
        'new_item_name'              => __( 'New Channel Name' ),
        'separate_items_with_commas' => __( 'Separate channels with commas' ),
        'add_or_remove_items'        => __( 'Add or remove channels' ),
        'choose_from_most_used'      => __( 'Choose from the most used channels' ),
        'menu_name'                  => __( 'Channels' ),
      ); 
    
    
      register_taxonomy( 'channel', 'post', array(
        'hierarchical'          => false,
        'labels'                => $labels,
        'show_ui'               => true,
        'show_admin_column'     => true,
        'update_count_callback' => '_update_post_term_count',
        'query_var'             => true,
        'rewrite'               => array( 
          'slug' => 'channel'
        ),
      ));

    }
    
    /**
     * Remove default post category and tag.
     */
    function remove_sub_menus() {

      remove_submenu_page('edit.php', 'edit-tags.php?taxonomy=category');
      remove_submenu_page('edit.php', 'edit-tags.php?taxonomy=post_tag');

    }
    
    /** 
     * Add query var for language.
    */
    function add_query_vars_filter( $vars ){

      $vars[] = "lang";
      return $vars;

    }
    
    /**
     * Update post list columns
     */
    function add_language_columns( $columns ) {

      return array(
        'cb'       => 'cb',
        'title'    => 'Title',
        'author'   => 'Author',
        'channels' => 'Channels',
        'language' => 'Language',
        'date'     => 'Date'
      );

    }
    
    /**
     * Validate custom columns value.
     */
    function custom_columns( $column, $post_id ) {

      switch ( $column ) {
        case 'language' :
          $language = get_post_meta( $post_id, 'language', true );
          echo $language;
              break;
        case 'channels' :
          $channel = wp_get_post_terms( get_the_ID(), 'channel', [ 'fields' => 'all' ] );
          if ( is_array( $channel ) && ! empty( $channel ) ) {
            $channel = sprintf('<a href="%s">%s</a>', get_edit_term_link( $channel[0]->term_id ), $channel[0]->name );
          } else {
            $channel = '-';
          }
          echo $channel;
              break;
      }

    }

    /**
     * Save ACF changes local
     */
    function acf_json_save_point( $path ) {
        
      $path = get_stylesheet_directory() . '/acf';
      
      return $path;
      
    }

    /**
     * Load ACF changes
     */
    function acf_json_load_point( $paths ) {

        unset($paths[0]);
        
        $paths[] = get_stylesheet_directory() . '/acf';
        
        return $paths;
        
    }

  }
  Alexa_Reader::get_instance();

}
