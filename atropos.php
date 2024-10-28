<?php
/*
Plugin Name: Atropos
Plugin URI: http://www.hostscope.com/wordpress-plugins/atropos_wordpress_plugin/
Description: Allows you to add an expiration date to posts.  This is the last date that the post should be shown (rather than the date on which it should be deleted).  
Author: John Leavitt
Version: 1.2
Author URI: http://www.jrrl.com/
*/




/* --------------------------------------------------
 * atropos_delete_expired_posts ()
 *
 * The workhorse function that is called by wp_cron to actually do the deletions.
 * 
 * We query for all expiring posts and then loop through them deleting as we go.
 *
 */

function atropos_delete_expired_posts () {
  global $wpdb;
  $result = $wpdb->get_results("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_atropos_expiration_date' AND meta_value < '" . date("Y/m/d") . "'");
  foreach ($result as $a) {
    wp_delete_post ($a->post_id);
  }
}



/* --------------------------------------------------
 * atropos_activate
 *
 * This is called when the plugin is first activated.
 *
 * We conjure up a time stamp for midnight tonight and set up the wp_cron event.
 *
 */

function atropos_activate () {
  $tomorrow = time() + 86400;
  $midnight  = mktime(0, 0, 0, 
		      date("m", $tomorrow), 
		      date("d", $tomorrow), 
		      date("Y", $tomorrow));
  wp_schedule_event($midnight, 'daily', 'atropos');
}



/* --------------------------------------------------
 * atropos_deactivate
 *
 * This is called when the plugin is deactivated.
 *
 * It just clears the wp_cron event.  Just tidying up.
 *
 */

function atropos_deactivate () {
  wp_clear_scheduled_hook('atropos');
}



/* --------------------------------------------------
 * atropos_add_column ()
 *
 * This adds an 'Expires' column to the post display table.
 *
 * We just add our field to the columns array and return it.
 *
 */

function atropos_add_column ($columns) {
  $columns['atropos'] = 'Expires';
  return $columns;
}



/* --------------------------------------------------
 * atropos_show_value ()
 *
 * This fills the 'Expires' column of the post display table.
 *
 * If we are looking at our field, we echo either the expiration date or nothing.
 *
 */

function atropos_show_value ($column_name) {
  global $wpdb, $post;
  if ($column_name === 'atropos') {
    $id = $post->ID;
    $ed = atropos_get_value ($id);
    echo ($ed ? $ed : "");
  }
}



/* --------------------------------------------------
 * atropos_custom_meta_box ()
 *
 * This creates the box on the post edit form.
 *
 * Not very interesting with the month logic borrowed from post.php.
 *
 */

function atropos_custom_meta_box () {
  global $wpdb, $post, $wp_locale;
  $id = $post->ID;
  $ed = atropos_get_value ($id);

  if ($ed) {
    list ($edy, $edm, $edd) = explode ('/', $ed);
  } else {
    $edy = $edm = $edd = '';
  }
  
  $month = "<select id=\"edm\" name=\"edm\">\n";
  $month .= "\t\t\t" . '<option value=""';
  if ($edm == '') {
    $month .= ' selected';
  }
  $month .= "></option>\n";
  for ( $i = 1; $i < 13; $i = $i +1 ) {
    $month .= "\t\t\t" . '<option value="' . zeroise($i, 2) . '"';
    if ( $i == $edm )
      $month .= ' selected="selected"';
    $month .= '>' . $wp_locale->get_month( $i ) . "</option>\n";
  }
  $month .= '</select>';
  $day = '<input type="text" id="edd" name="edd" value="' . $edd . '" size="2" maxlength="2" autocomplete="off"  />';
  $year = '<input type="text" id="edy" name="edy" value="' . $edy . '" size="4" maxlength="5" autocomplete="off"  />';

  echo "<p>Post expires at the end of $month $day $year</p>";
  echo "<p>Leave blank for no expiration date.</p>";
}



/* --------------------------------------------------
 * atropos_add_box ()
 *
 * This inserts the box creatred by atropos_custom_meta_box.
 *
 * Just a call to add_meta_box.
 *
 */

function atropos_add_box () {
  add_meta_box('atropos', __('Expiration Date'), 'atropos_custom_meta_box', 'post', 'advanced', 'high');
}



/* --------------------------------------------------
 * atropos_get_value ()
 *
 * This returns the expiration date for a given post ID.
 *
 * Queries and returns.
 *
 */

function atropos_get_value ($id) {
  global $wpdb;
  $query = "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = '_atropos_expiration_date' AND post_id='$id'";
  $ed = $wpdb->get_var($query);
  return ($ed ? $ed : '');
}



/* --------------------------------------------------
 * atropos_set_value ()
 *
 * This set the expiration date for a given post ID.
 *
 * A little messy figuring out if there was one before and if there is one now,
 * but then in just executes the appropriate query.
 *
 */

function atropos_set_value ($id, $ed) {
  global $wpdb;
  $olded = atropos_get_value ($id);
  $query = false;
  if ($olded) {
    if ($ed) {
      $query = "UPDATE {$wpdb->postmeta} SET meta_value='$ed' WHERE post_id='$id' AND meta_key='_atropos_expiration_date'"; 
    } else {
      $query = "DELETE FROM {$wpdb->postmeta} WHERE post_id='$id' AND meta_key='_atropos_expiration_date'";
    }
  } else if ($ed) {
    $query = "INSERT INTO {$wpdb->postmeta} (post_id,meta_key,meta_value) VALUES ('$id','_atropos_expiration_date','$ed')";
  }
  if ($query) {
    $wpdb->query ($query);
  }
}



/* --------------------------------------------------
 * atropos_save_expiration_date ()
 *
 * This saves the expiration date when the post is saved.
 *
 * It cobbles together the expiration date from the year,
 * month, and day fields and the calls atropos_set_value.
 *
 */

function atropos_save_expiration_date ($id) {
  $olded = atropos_get_value ($id);
  $edy = $_POST['edy'];
  $edm = $_POST['edm'];
  $edd = $_POST['edd'];
  if ($edy && $edm && $edd) {
    $ed = $edy . '/' . zeroise ($edm,2) . '/' . zeroise ($edd, 2);
  } else {
    $ed = '';
  }
  atropos_set_value ($id, $ed);
}



/* --------------------------------------------------
 * atropos_options_subpanel ()
 *
 * This shows the Atropos panel of the settings page.
 *
 * If it is a GET request, it checks for old Expiration
 * Date custom fields and offers to convert them if they
 * exist.
 *
 * If it is a POST request, old date conversion is done.
 *
 */

function atropos_options_subpanel () {
  global $wpdb;
  echo '<div class="wrap">';
  echo '<h2>Atropos Settings</h2>';

  if (isset($_POST['submit'])) {
    echo 'POST RESULTS';
    $query = 'SELECT * FROM ' . $wpdb->postmeta . ' WHERE meta_key = "Expiration Date"';
    $results = $wpdb->get_results($query);
    $count = 0;
    foreach ($results as $result) {
      $query = 'UPDATE ' . $wpdb->postmeta . " SET meta_key = '_atropos_expiration_date' WHERE meta_id=" . $result->meta_id;
      $wpdb->query ($query);
      $count++;
    }
    echo "<p>$count expiration dates have been imported.  Feel free to disable Expiration Date now ";
    echo "and use Atropos for all your post expiration needs.</p>";

  } else {

    $query = 'SELECT COUNT(post_id) FROM ' . $wpdb->postmeta . ' WHERE meta_key = "Expiration Date"';
    $result = $wpdb->get_var($query);
    if ((!$result) || ($result == 0)) {
      echo "<p>It doesn't look like there are any expiration dates set by another ";
      echo 'plugin.  If some had been found, you would have had the option to ';
      echo 'convert them over to use Atropos instead.  As it is, there is ';
      echo 'nothing to do here.  Have a nice day!</p>';
    } else {
      echo '<form action="" method="POST">';
      echo "  <p>Aha!  It looks like you've been using the Expiration Date plugin ";
      echo '  to expire your posts.  This plugin is the newer version of ';
      echo '  that plugin.  If you would like, Atropos can import your old expiration ';
      echo '  dates.</p>';
      echo '  <p class="submit"><input type="submit" name="submit" value="Convert Expiration Dates" /></p>';
      echo '</form>';
    }
  }



  echo '</div>';
}



/* --------------------------------------------------
 * atropos_add_options_page ()
 *
 * This adds the setting subpanel.
 *
 */

function atropos_add_options_page () {
  add_options_page('Atropos Options', 'Atropos', 8, basename(__FILE__), 'atropos_options_subpanel');
}



/* --------------------------------------------------
 * 
 * and now we put everything into place... so easy
 * and yet such a pain to get right
 *
 */

register_activation_hook   (__FILE__, 'atropos_activate');
register_deactivation_hook (__FILE__, 'atropos_deactivate');

add_action ('admin_menu',                 'atropos_add_options_page');
add_action ('manage_posts_custom_column', 'atropos_show_value');
add_filter ('manage_posts_columns',       'atropos_add_column');
add_action ('edit_form_advanced',         'atropos_add_box');
add_action ('save_post',                  'atropos_save_expiration_date');
add_action ('atropos',                    'atropos_delete_expired_posts');


?>
