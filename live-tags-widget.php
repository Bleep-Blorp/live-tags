<?php
namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Widget_Live_Tags extends Widget_Base {

   public function get_id() {
      return 'live-tags';
   }

   public function get_title() {
      return __( 'Live Tags Widget', 'elementor-custom-element' );
   }

   public function get_icon() {
      // Icon name from the Elementor font file, as per http://dtbaker.net/web-development/creating-your-own-custom-elementor-widgets/
      return 'post-list';
   }

   public function get_name() {
     return $this->get_title();
   }

   protected function _register_controls() {
   }

   protected function render( $instance = [] ) {
      // get our input from the widget settings.
      $custom_text = ! empty( $instance['some_text'] ) ? $instance['some_text'] : ' (no text was entered ) ';

      // this is the url to the live ajax function
      // $ajax_url = plugin_dir_url(__FILE__) . 'live-tag-ajax.php' ;
      $ajax_url = admin_url( 'admin-ajax.php' );
      $included_tags = str_replace('|', ', ', get_option('included_tags', ''));
      if( strlen($included_tags) > 0 ) {
        $tags = get_tags(array(
          "include" => $included_tags,
          "hide_empty" => true
        ));
      } else {
        $tags = [];
      }


      ?>
      <form class="live-tag-list">
         <?php
         foreach( $tags as $tag ){
           echo '<input type="checkbox" class="live-tag" value="'.$tag->term_id.'" id="checkbox_for_tag_'.$tag->term_id.'"></input>
                 <label for="checkbox_for_tag_'.$tag->term_id.'">' . $tag->name . ' <span data-original-count='.$tag->count.' class="tag-count">'.$tag->count.'</span></label>';
         }
         wp_reset_query();
         ?>
      </form>
      <script type="text/javascript">
        function buildItem(page) {
          return '<div class="live-tag-page"><a href="'+page.link+'"><h3>'+page.title+'</h3><p>' + page.body + '</p></a></div>';
        }

        function handleResponse(response) {
          updateTagCount(response.tags);
          var container = document.querySelector('.live-tag-container');
          container.innerHTML = response.pages.map(buildItem).join("\n");
        }

        function resetTags() {
          var tags = document.querySelectorAll('.live-tag-list input[type="checkbox"]');
          for(var i = 0; i < tags.length; i++){
            var counter = tags[i].nextElementSibling.querySelector('span');
            tags[i].disabled = false;
            counter.innerText = counter.getAttribute('data-original-count');
          }
        }

        function updateTagCount(availableTags){
          if(Object.keys(availableTags).length){
            var tags = document.querySelectorAll('.live-tag-list input[type="checkbox"]');
            for(var i = 0; i < tags.length; i++){
              var tag = tags[i];
              var counter = tag.nextElementSibling.querySelector('span');
              if (availableTags[tag.value]) {
                counter.innerText = availableTags[tag.value];
                tag.disabled = false;
              } else {
                counter.innerText = '0';
                tag.disabled = true;
              }
            }
          } else {
            resetTags();
          }
        }

        function loadResponse() {
          var tags = document.querySelector('.live-tag-list');
          var selectedTags = tags.querySelectorAll('input[type="checkbox"]');
          var selectedTagValues = [];
          for(var i = 0; i < selectedTags.length; i++){
            if(selectedTags[i].checked){
              selectedTagValues.push(selectedTags[i].value);
            }
          }

          var data = {
        		'action': 'live_tags',
            'tags':   selectedTagValues
        	};

        	jQuery.post('<?php echo $ajax_url ?>', data, handleResponse);
        }

        var tags = document.querySelector('.live-tag-list');
        tags.addEventListener('change', loadResponse);
        document.addEventListener('DOMContentLoaded', loadResponse)
      </script>
      <div class="live-tag-container"></div>
      <?php
   }

   protected function content_template() {}

   public function render_plain_content( $instance = [] ) {}

}

Plugin::instance()->widgets_manager->register_widget_type( new Widget_Live_Tags );
