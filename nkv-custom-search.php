<?php
/**
 * Plugin Name: NKV Custom Search
 * Description: Custom Search by Categories and Custom Fields
 * Version: 1.1
 * Author: n.karalash@gmail.com
 * Author URI: https://nkv.com/
*/

defined('ABSPATH') || exit;



function wcs_enqueued_assets() {
	if (is_page()) {
		global $post;
		if (has_shortcode($post->post_content, 'wcs_search_box')) {
			wp_enqueue_style('wcs-styles', plugins_url('/css/style.css', __FILE__));
			wp_enqueue_script('wcs-scripts', plugins_url('/js/script.js', __FILE__));
		}
	}
}
add_action('wp_enqueue_scripts','wcs_enqueued_assets');



register_activation_hook(__FILE__, 'wcs_activation');

function wcs_activation() {
	if (!wp_next_scheduled('wcs_hourly_event')) {
		wp_schedule_event(time(), 'hourly', 'wcs_hourly_event');
	}
}
add_action('wcs_hourly_event', 'wcs_generate_cfarray');



register_deactivation_hook(__FILE__, 'wcs_deactivation');

function wcs_deactivation() {
	wp_clear_scheduled_hook('wcs_hourly_event');
	delete_option('wcs_custom-field-array');
}


function wcs_prep_cfarray() {
	return [
		[
			'key' => 'favorability',
			'label' => 'Favorability',
			'choices' => [],
			'select_name' => 'wcs_fav'
		],
		[
			'key' => 'stakeholder',
			'label' => 'Stakeholder',
			'choices' => [],
			'select_name' => 'wcs_sta'
		],
		[
			'key' => 'world_region',
			'label' => 'Region',
			'choices' => [],
			'select_name' => 'wcs_wor'
		],
		[
			'key' => 'country',
			'label' => 'Country',
			'choices' => [],
			'select_name' => 'wcs_cou'
		],
		[
			'key' => 'functional_tag',
			'label' => 'Functional Tag',
			'choices' => [],
			'select_name' => 'wcs_fun'
		],
		[
			'key' => 'content_type',
			'label' => 'Content Type',
			'choices' => [],
			'select_name' => 'wcs_con'
		],
		[
			'key' => 'source',
			'label' => 'Source',
			'choices' => [],
			'select_name' => 'wcs_sou'
		],
		[
			'key' => 'month_and_year',
			'label' => 'Month and Year',
			'choices' => [],
			'select_name' => 'wcs_mon'
		]
	];
}



function wcs_generate_cfarray() {

	global $wpdb;

	$cf_array = wcs_prep_cfarray();

	foreach ($cf_array as $k => $v) {
		$choices = $wpdb->get_results('SELECT DISTINCT meta_value FROM ' . $wpdb->postmeta . ' WHERE meta_key = \'' . $v['key'] . '\' ORDER BY meta_value ASC');
		foreach ($choices as $kk => $vv) {
			$choices[$kk] = $vv->meta_value;
		}
		$cf_array[$k]['choices'] = $choices;
	}

	update_option('wcs_custom-field-array', $cf_array);
	return $cf_array;
}


function wcs_get_cfarray() {

	$cf_array = get_option('wcs_custom-field-array');

	if ($cf_array !== false ) {
		return $cf_array;
	} else {
		return wcs_generate_cfarray();
	}
}



function wcs_query_vars($vars) {
	$vars[] = 'wcs';
	$vars[] = 'wcs_cat';
	$vars[] = 'wcs_tag';
	foreach (wcs_prep_cfarray() as $v) {
		$vars[] = $v['select_name'];
	}
	return $vars;
}
add_filter('query_vars', 'wcs_query_vars');



function wcs_get_tags_old() {
	$tags = explode("\n", get_option('wmt_all-tags'));
	foreach ($tags as $k => $v) {
		$tags[$k] = trim($v);
		if (empty($tags[$k])) {
			unset($tags[$k]);
		}
	}
	return $tags;
}

function wcs_get_tags() {
	global $wpdb;

	$tag_names = $wpdb->get_results('SELECT t.name FROM wpmo_terms AS t INNER JOIN wpmo_term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy IN (\'post_tag\') ORDER BY t.name ASC');
	foreach ($tag_names as $v) {
		$tags[] = $v->name;
	}
	return $tags;
}

function wcs_search_form($visible = true) { 

	$wp_dropdown_cats = wp_dropdown_categories([
							'name' => 'wcs_cat',
							'selected' => get_query_var('wcs_cat', ''),
							'value_field' => 'term_id',
							'orderby' => 'name',
							'class' => '',
							'echo' => 0,
							'show_option_none' => ' ',
							'option_none_value' => ''
						]);

	$form_class = $visible ? 'wcs-form' : 'wcs-form wcs-hidden';

	$form = '
<div class="' . $form_class . '" id="wcs-form">
	<form method="get" action="' . get_permalink() . '">
		<input type="hidden" name="wcs" value="1" />
		<label>Category</label>
		<div class="wcs-select">' . $wp_dropdown_cats . '</div>
		<label>Tag</label>
		<div class="wcs-select">
			<select type="text" name="wcs_tag">
				<option value=""></option>';

	foreach(wcs_get_tags() as $v) {
		$selected = ($v == get_query_var('wcs_tag')) ? 'selected="selected"' : '';
		$form .= '<option value="' . $v .'" ' . $selected . '>' . $v . '</option>';
	}

	$form .= '
			</select>
		</div>';

	foreach (wcs_get_cfarray() as $v) {
		$form .= ' 
		<label>' . $v['label'] . '</label>
		<div class="wcs-select">
			<select type="text" name="' . $v['select_name'] . '">
				<option value=""></option>';

		foreach ($v['choices'] as $vv) {
			$selected = ($vv == get_query_var($v['select_name'])) ? 'selected="selected"' : '';
			$form .= '<option value="' . $vv .'" ' . $selected . '>' . $vv . '</option>';
		}

		$form .= ' 
			</select>
		</div>';
	}

	$form .= '
		<div class="wcs-submit">
			<input type="submit" value="Search" />
		</div>
	</form>
</div>';

	return $form;
} 



function wcs_search_box() {

	if (is_admin()) { return; }

	if (!get_query_var('wcs') || (get_query_var('wcs') != 1)) {

		echo wcs_search_form(); 

	} else {

		echo '
<p><a id="wcs-form-toggle" href="javascript:wcs_form_show()">&#x25BC; Show Search Options</a></p>';
		echo wcs_search_form(false); 

		$args = [];
		$args['ep_integrate'] = true;
		$args['post_type'] = 'post';
		$args['posts_per_page'] = 18;
		$args['paged'] = (get_query_var('paged')) ? get_query_var('paged') : 1;

		if (get_query_var('wcs_cat')) {
			$args['tax_query'] = [ 
				[ 
					'taxonomy' => 'category',
					'field' => 'term_id',
					'terms' => get_query_var('wcs_cat')
				]
			];
		}

		if (get_query_var('wcs_tag')) {
			$args['ep_integrate'] = false;
			$tag = get_term_by('slug', get_query_var('wcs_tag'),'post_tag');
			$args['tag__in'] = $tag->term_id;
		}

		foreach (wcs_prep_cfarray() as $v) {
			if (get_query_var($v['select_name'])) {
				$args['meta_query'][] = [
					'key' => $v['key'],
					'value' => get_query_var($v['select_name']),
					'compare' => '='
				];
			}
		}

		if (is_array($args['meta_query']) && (count($args['meta_query']) >= 2)) {
			$args['meta_query']['relation'] = 'AND';
		}

		//$start_memory = memory_get_usage();
		$the_query = new WP_Query($args);
		//echo '<div> Memory used by query: ' . (memory_get_usage() - $start_memory) . '</div>';

		if ($the_query->have_posts()) {
			while ($the_query->have_posts()) : $the_query->the_post();
				get_template_part('post', 'search');
			endwhile;
		} else {
			echo '<p>Sorry, but nothing matched your search criteria.</p>';
		}

		echo '
<div class="pagenav">
	<div class="alignleft">' . get_previous_posts_link('Previous', $the_query->max_num_pages) . '</div>
	<div class="alignright">' . get_next_posts_link('Next', $the_query->max_num_pages) . '</div>
</div>';

		wp_reset_postdata();
	}
}
add_shortcode( 'wcs_search_box', 'wcs_search_box' );


?>
