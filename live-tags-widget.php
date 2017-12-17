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

   protected function sort_by_name($a, $b){
     $a = strtolower($a->name);
     $b = strtolower($b->name);
     if ($a == $b) {
       return 0;
     }
     return ($a < $b) ? -1 : 1;
   }

   protected function _register_controls() {

     $tags = get_tags();
     usort($tags, array( $this, 'sort_by_name'));

     $this->start_controls_section(
       'section_tagz',[
         'label' => __('Tags to Show', 'ba-live-tags'),
         'tab' => Controls_Manager::TAB_CONTENT
       ]
     );

     foreach ( $tags as $tag ) {
       $this->add_control(
         $tag->term_id, [
           'label' => __( $tag->name, 'ba-live-tags' ),
           'type' => Controls_Manager::SWITCHER,
         ]
      );
     }

     $this->end_controls_section();

     $this->start_controls_section(
       'section_settingz',[
         'label' => __('Config', 'ba-live-tags'),
         'tab' => Controls_Manager::TAB_ADVANCED
       ]
     );

     $this->add_control(
       'show_search', [
         'label' => __( "Show Search", 'ba-live-tags' ),
         'type' => Controls_Manager::SWITCHER,
         'default' => 'yes',
       ]
     );

     $this->add_control(
       'show_count', [
         'label' => __( "Show Count", 'ba-live-tags' ),
         'type' => Controls_Manager::SWITCHER,
       ]
     );

     $this->add_control(
       'show_body', [
         'label' => __( "Show Article Body", 'ba-live-tags' ),
         'type' => Controls_Manager::SWITCHER,
       ]
     );

     $this->add_control(
       'show_all_on_blank', [
         'label' => __( "Show All Pages if No Matches", 'ba-live-tags' ),
         'type' => Controls_Manager::SWITCHER,
       ]
     );

     $this->add_control(
       'tag_label', [
         'label' => __( "Tag Filter Label", 'ba-live-tags' ),
         'type' => Controls_Manager::TEXT,
         'placeholder' => __( 'Filters', 'your-plugin' ),
       ]
     );

     $this->add_control(
       'search_label', [
         'label' => __( "Search Label", 'ba-live-tags' ),
         'type' => Controls_Manager::TEXT,
         'placeholder' => __( 'Search', 'your-plugin' ),
       ]
     );

     $this->add_control(
       'sort_order', [
         'label' => __( "Page Sort Order", 'ba-live-tags' ),
         'type' => Controls_Manager::SELECT,
         'default' => 'alpha',
         'options' => [
     	     'alpha'  => __( 'Alphabetically', 'ba-live-tags' ),
     	     'reverse_cron' => __( 'Newest First', 'ba-live-tags' ),
         ]
       ]
     );

     $this->end_controls_section();
   }

   protected function render( $instance = [] ) {
      // get our input from the widget settings.
      $settings = $this->get_settings();

      $all_tags = get_tags();
      usort($all_tags, array( $this, 'sort_by_name'));

      $tags = [];

      foreach($all_tags as $tag) {
        if($settings[$tag->term_id] == 'yes'){
          $tags []= $tag;
        }
      }

      // this is the url to the live ajax function
      // $ajax_url = plugin_dir_url(__FILE__) . 'live-tag-ajax.php' ;
      $ajax_url = admin_url( 'admin-ajax.php' );
      $id = uniqid();
      ?>
      <div id="live-tag-<?php echo $id?>">
        <form class="live-tag-list">
            <?php if (!empty($settings['tag_label'])) { ?>
              <div class="tag-label">
                <label><?php echo $settings['tag_label'] ?></label>
              </div>
            <?php } ?>
           <?php
           foreach( $tags as $tag ){
             echo '<input type="checkbox" class="live-tag" value="'.$tag->term_id.'" id="checkbox_for_tag_'.$tag->term_id.'"></input>';
             echo '<label class="as-button" for="checkbox_for_tag_'.$tag->term_id.'">' . $tag->name ;
             if ($settings['show_count'] == 'yes') {
               echo '<span data-original-count='.$tag->count.' class="tag-count">'.$tag->count.'</span>';
             }
             echo '</label>';
           }

           if ($settings['show_search'] == 'yes') {
             echo '<div class="live-tag-search-container">';
             if (!empty($settings['search_label'])) {
               echo '<label class="live-tag-search-label" for="live-tag-search">' . $settings['search_label'] . '</label>' ;
             }
             echo '<input type="search" name="live-tag-search" class="live-tag-search"></input>';
             echo '</div>';
           }

           wp_reset_query();
           ?>
        </form>
        <div class="live-tag-container"></div>
      </div>
      <script type="text/javascript">
        var thisId = "live-tag-<?php echo $id?>";
        var thisContainer = document.getElementById(thisId);
        var search = thisContainer.querySelector('input[name="live-tag-search"]');

        function debounce(func, wait, immediate) {
        	var timeout;
        	return function() {
        		var context = this, args = arguments;
        		var later = function() {
        			timeout = null;
        			if (!immediate) func.apply(context, args);
        		};
        		var callNow = immediate && !timeout;
        		clearTimeout(timeout);
        		timeout = setTimeout(later, wait);
        		if (callNow) func.apply(context, args);
        	};
        };

        function buildItem(page) {
          var content = '<div class="live-tag-page"><a href="'+page.link+'">';
              content += '<h3>'+page.title+'</h3>';
              <?php if ($settings['show_body'] == 'yes') {?>
                content += '<p>' + page.body + '</p>';
              <?php } ?>
              content += '</a></div>';
          return content;
        }

        function handleResponse(response) {
          updateTagCount(response.tags);
          var container = thisContainer.querySelector('.live-tag-container');
          container.innerHTML = response.pages.map(buildItem).join("\n");
        }

        function resetTags() {
          var tags = thisContainer.querySelectorAll('.live-tag-list input[type="checkbox"]');
          for(var i = 0; i < tags.length; i++){
            var counter = tags[i].nextElementSibling.querySelector('span');
            tags[i].disabled = false;
            if(counter){
              counter.innerText = counter.getAttribute('data-original-count');
            }
          }
        }

        function updateTagCount(availableTags){
          if(Object.keys(availableTags).length){
            var tags = thisContainer.querySelectorAll('.live-tag-list input[type="checkbox"]');
            for(var i = 0; i < tags.length; i++){
              var tag = tags[i];
              var counter = tag.nextElementSibling.querySelector('span');
              if (availableTags[tag.value]) {
                if(counter){
                  counter.innerText = availableTags[tag.value];
                }
                tag.disabled = false;
              } else {
                if(counter){
                  counter.innerText = '0';
                }
                tag.disabled = true;
              }
            }
          } else {
            resetTags();
          }
        }

        function loadResponse() {
          var tags = thisContainer.querySelectorAll('input[type="checkbox"]');
          var selectedTagValues = [];
          var unselectedTagValues = [];
          for(var i = 0; i < tags.length; i++){
            if(tags[i].checked){
              selectedTagValues.push(tags[i].value);
            } else {
              unselectedTagValues.push(tags[i].value);
            }
          }

          var data = {
        		'action'       : 'live_tags',
            'tags'         : selectedTagValues,
            'order'        : '<?php echo $settings['sort_order'] ?>',
            'unselected' : <?php echo $settings['show_all_on_blank'] ? unselectedTagValues : []  ?>
        	};

          // add Search
          if(search) {
            data['search'] = search.value;
          }

        	jQuery.post('<?php echo $ajax_url ?>', data, handleResponse);
        }

        var tags = thisContainer.querySelector('.live-tag-list');
        tags.addEventListener('change', loadResponse);
        document.addEventListener('DOMContentLoaded', loadResponse);
        search.addEventListener('keyup', debounce(loadResponse, 250));
      </script>
      <?php
   }

   protected function content_template() {}

   public function render_plain_content( $instance = [] ) {}

}

Plugin::instance()->widgets_manager->register_widget_type( new Widget_Live_Tags );
