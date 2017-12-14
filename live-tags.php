<?php
/*
Plugin Name: Live Tags
Plugin URI:  http://no-real-url.com
Description: Allows for live tag filtering
Version:     1.0
Author:      Brian Anderson
Author URI:  http://link to your website
License:     GPL2 etc
License URI: http://link to your plugin license
*/


defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class LiveTagsAdmin {
  public function __construct() {
    if ( is_admin() ){
      add_action( 'admin_menu', array( $this, 'initialize_menu' ) );
      add_action( 'admin_init', array( $this, 'initialize_settings' ) );
      $this->save_form();
    }
  }

  public function initialize_menu() {
  	//create new top-level menu
    add_options_page( 'Live Tag Filtering', 'Live Tags', 'manage_options', 'live-tag', array($this, 'settings_page') );
  }

  public function initialize_settings() {
    register_setting( 'live_tag_settings_group', 'included_tags');
  }

  public function save_form() {
    if( $_POST['included_tags'] ){
      update_option('included_tags', $_POST['included_tags']);
    }
  }

  public function settings_page() {
    require 'admin/settings.php';
  }
}

class LiveTagViewer {
  public function __construct() {
    $this->registerAjax();
  }

  public function registerAjax() {
    add_action(        'wp_ajax_live_tags', array( $this, 'paginated_posts_for_tag') );
    add_action( 'wp_ajax_nopriv_live_tags', array( $this, 'paginated_posts_for_tag' ) );
    add_filter(     'allowed_http_origins', array( $this, 'add_allowed_origins') );
    add_action(       'wp_enqueue_scripts', array( $this, 'register_plugin_styles' ) );
  }

  public function add_allowed_origins($origins) {
    $origins[] = 'http://localhost';
    return $origins;
  }

  public function register_plugin_styles() {
		wp_register_style( 'live_tags', plugins_url( 'ba-live-tags/css/live-tag-style.css' ) );
		wp_enqueue_style( 'live_tags' );
	}

  public function paginated_posts_for_tag() {
    $tags = $_POST['tags'];
    $pages = [];

    if ($tags) {
      $args = array(
        'tag__and' => $tags,
        'post_type' => array( 'page' ),
      );

      $query = new WP_Query( $args );

      if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
          $query->the_post();
          $pages[] = array(
            'title' => get_the_title($query->post->ID),
            'body' => substr(wp_strip_all_tags($query->post->post_content), 0, 300). '...',
            'link' => get_post_permalink($query->post->ID)
          );
        }
      }
      wp_reset_postdata();
    }

    wp_send_json($pages);
  }

}

new LiveTagsAdmin();
new LiveTagViewer();



// Elementor custom element
// From https://dtbaker.net/blog/web-development/2016/10/creating-your-own-custom-elementor-widgets/

class ElementorCustomElement {

   private static $instance = null;

   public static function get_instance() {
      if ( ! self::$instance )
         self::$instance = new self;
      return self::$instance;
   }

   public function init(){
      add_action( 'elementor/widgets/widgets_registered', array( $this, 'widgets_registered' ) );
   }

   public function widgets_registered() {

      // We check if the Elementor plugin has been installed / activated.
      if(defined('ELEMENTOR_PATH') && class_exists('Elementor\Widget_Base')){

         // We look for any theme overrides for this custom Elementor element.
         // If no theme overrides are found we use the default one in this plugin.

         $widget_file = 'plugins/elementor/my-widget.php';
         $template_file = locate_template($widget_file);
         if ( !$template_file || !is_readable( $template_file ) ) {
            $template_file = plugin_dir_path(__FILE__).'live-tags-widget.php';
         }
         if ( $template_file && is_readable( $template_file ) ) {
            require_once $template_file;
         }
      }
   }
}

ElementorCustomElement::get_instance()->init();
