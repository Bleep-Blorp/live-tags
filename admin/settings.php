<div class="wrap">
<h1>Live Tags Settings</h1>

<p>Select the tags to be included in the encyclopedia live selection.</p>

<form class="tag-settings" method="post">
    <?php settings_fields( 'live_tag_settings_group' ); ?>
    <?php do_settings_sections( 'live_tag_settings_group' ); ?>
    <input type="hidden" name="included_tags" value="<?php echo get_option('included_tags'); ?>" />
    <table class="form-table">
        <tr valign="top">
        <th scope="row">Included Tags</th>
        <td>
          <?php
          function sort_by_name($a, $b){
            $a = strtolower($a->name);
            $b = strtolower($b->name);
            if ($a == $b) {
              return 0;
            }
            return ($a < $b) ? -1 : 1;
          }

          $tags = get_tags();
          usort($tags, "sort_by_name");

          foreach ( $tags as $tag ) {
            ?>
            <input value="<?php echo $tag->term_id ?>" name="_ignore_these_tags[]" id="tag_<?php echo $tag->term_id ?>" type="checkbox" />
            <label for="tag_<?php echo $tag->term_id ?>" >
              <?php echo strtoupper($tag->name) ?>
            </label><br />
          <?php } ?>

          <script type="text/javascript">
            var form = document.querySelector('form.tag-settings');
            var formFields = form.querySelectorAll('input[type="checkbox"]');
            var field = form.querySelector('input[name="included_tags"]');
            var settings = [];

            function updateSettingsString() {
              settings = [];
              for(var i = 0; i < formFields.length; i++){
                if(formFields[i].checked){
                  settings.push(formFields[i].value)
                }
              }

              console.log('settings', settings.join('|'));
              field.value = settings.join('|');
            }

            function updateFormFromSettingField(settingString){
              settingString.split('|').map(function(val){
                form.querySelector('input[value="'+val+'"]').checked = true;
                console.log('checking:',val);
              })

            }

            updateFormFromSettingField(field.value);
            form.addEventListener('change', updateSettingsString);

          </script>

        </td>
        </tr>
    </table>

    <?php submit_button(); ?>

</form>
</div>
