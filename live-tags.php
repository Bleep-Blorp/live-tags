<?php
/*
Plugin Name: Live Tags
Plugin URI:  https://github.com/Bleep-Blorp/live-tags
Description: Allows for live tag filtering
Version:     3.0
Author:      Brian Anderson
Author URI:  https://github.com/Bleep-Blorp/
License:     MIT
License URI: https://github.com/Bleep-Blorp/live-tags
*/


defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class LiveTagViewer {
  public function __construct() {
    $this->registerAjax();
  }

  public function registerAjax() {
    add_action(        'wp_ajax_live_tags', array( $this, 'ajax_page_response') );
    add_action( 'wp_ajax_nopriv_live_tags', array( $this, 'ajax_page_response' ) );
    add_filter(     'allowed_http_origins', array( $this, 'add_allowed_origins') );
    add_action(       'wp_enqueue_scripts', array( $this, 'register_plugin_styles' ) );
  }

  public function add_allowed_origins($origins) {
    $origins[] = 'http://localhost';
    return $origins;
  }

  public function register_plugin_styles() {
		wp_register_style( 'live_tags', plugin_dir_url( __FILE__ ) . 'css/live-tag-style.css' );
		wp_enqueue_style( 'live_tags' );
	}

  public function ajax_page_response() {
    $tags = $_POST['tags'];
    $unselected_tags = $_POST['unselected'];
    $pages = [];
    $available_tags = [];
    $query_type = 'tag__and';

    if (!empty($tags)) {
      $tags_to_show = $tags;
    } else {
      $tags_to_show = $unselected_tags;
      $query_type = 'tag__in';
    }

    if ($tags_to_show) {
      // the crazy word press query reference
      // https://codex.wordpress.org/Class_Reference/WP_Query
      $args = array(
        $query_type => $tags_to_show,
        'post_type' => array( 'page' ),
        'nopaging' => true,
      );

      switch ($_POST['order']) {
        case 'alpha':
          $args['orderby'] = 'title';
          $args['order'] = 'asc';
        break;
      }

      if (!empty($_POST['search'])) {
        $args['s'] = $_POST['search'];
      }

      $query = new WP_Query( $args );

      if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
          $query->the_post();

          // Filter out tags still available for this result set
          foreach(wp_get_post_tags($query->post->ID) as $tag) {
            $available_tags[$tag->term_id] += 1;
          }

          $pages[] = array(
            'title' => get_the_title($query->post->ID),
            'body' => substr(wp_strip_all_tags($query->post->post_content), 0, 300). '...',
            'link' => get_post_permalink($query->post->ID)
          );
        }
      }
      wp_reset_postdata();
    }

    wp_send_json(array('pages' => $pages,
                       'tags' => $available_tags));
  }

}

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
