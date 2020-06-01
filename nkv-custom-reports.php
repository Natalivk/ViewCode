<?php
/**
 * Plugin Name: NKV Custom Reports
 * Description: Manage Tags for the "Tag & Content Type" report and Categories for the "Category & World Region" report. Set up additional rewrites for custom feeds.
 * Version: 1.0
 * Author: n.karalash@gmail.com
 * Author URI: https://nkv.com/
*/


defined('ABSPATH') || exit;


/* Manage Tags and Categories */


function wcr_menu() {
	add_management_page('NKV Custom Reports Options', 'NKV Custom Reports', 'manage_options', 'nkv-custom-reports', 'nkv_custom_reports_page');
}
add_action('admin_menu', 'wcr_menu');


function nkv_custom_reports_page() {
	if (!current_user_can('manage_options')) {
		wp_die(__( 'You do not have sufficient permissions to access this page.'));
	}
?>
<div class="wrap">
	<h1>NKV Custom Reports</h1>
	<br />
	<form method="post" action="options.php">
		<?php settings_fields('nkv-custom-reports-settings'); ?>
		<?php do_settings_sections('nkv-custom-reports-settings'); ?>

		<h2>Manage Tags for the "Tag & Content Type" report</h2>
		<p>Enter tags below, one per line:</p>
		<textarea name="wcr_managed_tags" rows="10" cols="50" class="small-text">
            <?php echo get_option('wcr_managed_tags'); ?>
        </textarea>

		<h2>Manage Categories for the "Category & World Region" report</h2>
		<table class="widefat striped">
<?php 
	foreach (wcr_get_top_categories() as $k => $v) {
		$cb_name = 'wcr_managed_cat_' . $k;
		$cb_checked = get_option($cb_name) ? ' checked' : '';
		echo '
			<tr>
				<td>
					<input name="' . $cb_name . '" id="' . $cb_name . '" type="checkbox"' . $cb_checked . '>
					<label for="' . $cb_name . '">' . $v . '</label>
				</td>
			</tr>';
	}
?>
		</table>

		<?php submit_button(); ?>
	</form>
</div>
<?php	
}


function wcr_update_options() {
	register_setting('nkv-custom-reports-settings', 'wcr_managed_tags');
	foreach (wcr_get_top_categories() as $k => $v) {
		register_setting('nkv-custom-reports-settings', 'wcr_managed_cat_' . $k);
	}
}
add_action('admin_init', 'wcr_update_options');


function wcr_uninstall() {
	global $wpdb;
	$wpdb->query('DELETE FROM ' . $wpdb->options . ' WHERE option_name LIKE \'wcr_managed_%\'');
}
register_uninstall_hook(__FILE__, 'wcr_uninstall');


/* Custom Reports */


function wcr_add_vars($vars)
{
	$vars[] = 'wcr';
	$vars[] = 'wcr-rss';
	$vars[] = 'wcr-tag';
	$vars[] = 'wcr-type';
	$vars[] = 'wcr-cat';
	$vars[] = 'wcr-region';
	return $vars;
}
add_filter('query_vars', 'wcr_add_vars');


function wcr_add_rewrite() {
	foreach(wcr_get_pages_by_template('report-tag-n-content_type.php') as $page) {
		add_rewrite_rule(
			'^' . $page->post_name . '/tag-(.*)/type-(.*)/feed/?$',
			'index.php?page_id=' . $page->ID . '&wcr=1&wcr-tag=$matches[1]&wcr-type=$matches[2]&wcr-rss=1',
			'top');
	}
	foreach(wcr_get_pages_by_template('report-cat-n-world_region.php') as $page) {
		add_rewrite_rule(
			'^' . $page->post_name . '/cat-(.*)/region-(.*)/feed/?$',
			'index.php?page_id=' . $page->ID . '&wcr=1&wcr-cat=$matches[1]&wcr-region=$matches[2]&wcr-rss=1',
			'top');
	}
}
add_action('init', 'wcr_add_rewrite');

function wcr_flush_rewrite_rules() {
	$opt_name = 'wcr_flush_rewrites';

	$version = filemtime(__FILE__);

	$pages = [];
	foreach ([ 'report-tag-n-content_type.php', 'report-cat-n-world_region.php' ] as $template) {
		$tmp = [];      
		foreach(wcr_get_pages_by_template($template) as $page) {
			$tmp[] = $page->ID;
		}
		sort($tmp);
		$pages[$template] = $tmp;
	}

	$defaults = [ 'version' => 0 , 'pages' => [] ];
	
	$r = wp_parse_args(get_option($opt_name), $defaults);
	if (($r['version'] != $version) || ($r['pages'] != $pages)) {
		flush_rewrite_rules();
		$args = [ 'version' => $version, 'pages' => $pages ];
		update_option($opt_name, $args);
	}
}
add_action('init', 'wcr_flush_rewrite_rules');


/* Service functions */


function wcr_get_top_categories() {
	foreach (get_categories([ 'orderby' => 'name', 'parent' => 0 ]) as $v) {
		$cats[$v->cat_ID] = $v->cat_name;
	}
	return $cats;
}


function wcr_get_pages_by_template($template) {
	return (!empty($template)) ? get_posts([ 'post_type' => 'page', 'meta_key' => '_wp_page_template', 'meta_value' => $template ]) : [];
}


?>
