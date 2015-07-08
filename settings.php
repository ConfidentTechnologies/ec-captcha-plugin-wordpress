<h1>Settings file included:</h1>
<div class="wrap">
   <a name="confidentCaptcha"></a>
   <h2><?php _e('Confident CAPTCHA Options', 'confidentCaptcha'); ?></h2>
   <p><?php _e('Confident CAPTCHA is an image based CAPTCHA solution that stops spam and is user friendly.', 'confidentCaptcha'); ?></p>
   
   <form method="post" action="options.php">
      <?php settings_fields('confidentCaptcha_options_group'); ?>

      <h3><?php _e('Authentication', 'confidentCaptcha'); ?></h3>
      <p><?php _e('These keys are required before you are able to do anything else.', 'confidentCaptcha'); ?> <?php _e('You can get the keys', 'confidentCaptcha'); ?> <a href="http://www.confidenttechnologies.com/content/get-confident-captcha-today" target="_blank" title="<?php _e('Get your confidentCaptcha API Keys', 'confidentCaptcha'); ?>"><?php _e('here', 'confidentCaptcha'); ?></a>.</p>
      
      <table class="form-table">
         <tr valign="top">
            <th scope="row"><?php _e('Api Key', 'confidentCaptcha'); ?></th>
            <td>
               <input type="text" name="confidentCaptcha_options[api_key]" size="40" value="<?php echo $this->options['api_key']; ?>" />
            </td>
         </tr>
         <tr valign="top">
            <th scope="row"><?php _e('Public Key', 'confidentCaptcha'); ?></th>
            <td>
               <input type="text" name="confidentCaptcha_options[public_key]" size="40" value="<?php echo $this->options['public_key']; ?>" />
            </td>
         </tr>
      </table>
      
      <h3><?php _e('Comment Options', 'confidentCaptcha'); ?></h3>
      <table class="form-table">
         <tr valign="top">
            <th scope="row"><?php _e('Activation', 'confidentCaptcha'); ?></th>
            <td>
               <input type="checkbox" id ="confidentCaptcha_options[show_in_comments]" name="confidentCaptcha_options[show_in_comments]" value="1" <?php checked('1', $this->options['show_in_comments']); ?> />
               <label for="confidentCaptcha_options[show_in_comments]"><?php _e('Enable for comments form', 'confidentCaptcha'); ?></label>
            </td>
         </tr>
         
         <tr valign="top">
            <th scope="row"><?php _e('Target', 'confidentCaptcha'); ?></th>
            <td>
               <input type="checkbox" id="confidentCaptcha_options[bypass_for_registered_users]" name="confidentCaptcha_options[bypass_for_registered_users]" value="1" <?php checked('1', $this->options['bypass_for_registered_users']); ?> />
               <label for="confidentCaptcha_options[bypass_for_registered_users]"><?php _e('Hide for Registered Users who can', 'confidentCaptcha'); ?></label>
               <?php $this->capabilities_dropdown(); ?>
            </td>
         </tr>

         <tr valign="top">
            <th scope="row"><?php _e('Tab Index', 'confidentCaptcha'); ?></th>
            <td>
               <input type="text" name="confidentCaptcha_options[comments_tab_index]" size="10" value="<?php echo $this->options['comments_tab_index']; ?>" />
            </td>
         </tr>
      </table>
      
      <h3><?php _e('Registration Options', 'confidentCaptcha'); ?></h3>
      <table class="form-table">
         <tr valign="top">
            <th scope="row"><?php _e('Activation', 'confidentCaptcha'); ?></th>
            <td>
               <input type="checkbox" id ="confidentCaptcha_options[show_in_registration]" name="confidentCaptcha_options[show_in_registration]" value="1" <?php checked('1', $this->options['show_in_registration']); ?> />
               <label for="confidentCaptcha_options[show_in_registration]"><?php _e('Enable for registration form', 'confidentCaptcha'); ?></label>
            </td>
         </tr>
         
         <tr valign="top">
            <th scope="row"><?php _e('Tab Index', 'confidentCaptcha'); ?></th>
            <td>
               <input type="text" name="confidentCaptcha_options[registration_tab_index]" size="10" value="<?php echo $this->options['registration_tab_index']; ?>" />
            </td>
         </tr>
         <tr valign="top">
            <th scope="row"><?php _e('Login page', 'confidentCaptcha'); ?></th>
            <td>
               <input type="checkbox" id ="confidentCaptcha_options[show_in_login_page]" name="confidentCaptcha_options[show_in_login_page]" value="1" <?php checked('1', $this->options['show_in_login_page']); ?> />
               <label for="confidentCaptcha_options[show_in_login_page]"><?php _e('Enable for Login', 'confidentCaptcha'); ?></label>
            </td>
         </tr>
         <tr valign="top">
            <th scope="row"><?php _e('Lost Password', 'confidentCaptcha'); ?></th>
            <td>
               <input type="checkbox" id ="confidentCaptcha_options[show_in_lost_password]" name="confidentCaptcha_options[show_in_lost_password]" value="1" <?php checked('1', $this->options['show_in_lost_password']); ?> />
               <label for="confidentCaptcha_options[show_in_lost_password]"><?php _e('Enable for Lost Password', 'confidentCaptcha'); ?></label>
            </td>
         </tr>  
      </table>

      <h3><?php _e('Advanced Options', 'confidentCaptcha'); ?></h3>
      <table class="form-table">
          <tr valign="top">
              <th scope="row"><?php _e('Failure Policy', 'confidentCaptcha'); ?></th>
              <td>
                  <?php $this->fp_dropdown(); ?>
              </td>
          </tr>
          <tr valign="top">
              <th scope="row"><?php _e('Debug Mode', 'confidentCaptcha'); ?></th>
              <td>
                  <?php $this->debug_mode_dropdown(); ?>
              </td>
          </tr>
      </table>


      <p class="submit"><input type="submit" class="button-primary" title="<?php _e('Save Confident CAPTCHA Options') ?>" value="<?php _e('Save Confident CAPTCHA Changes') ?> &raquo;" /></p>
   </form>
   
   <?php do_settings_sections('confidentCaptcha_options_page'); ?>
</div>
