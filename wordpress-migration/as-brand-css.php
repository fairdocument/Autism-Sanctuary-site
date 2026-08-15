<?php
/**
 * Plugin Name: Autism Sanctuary Brand CSS
 * Description: Loads EmDash-aligned brand CSS and keeps page HTML intact.
 */

add_action('wp_enqueue_scripts', function () {
	$rel = 'wordpress-migration/custom.css';
	$path = ABSPATH . $rel;
	if (!file_exists($path)) {
		return;
	}
	wp_register_style('as-brand', home_url('/' . $rel), [], (string) filemtime($path));
	wp_enqueue_style('as-brand');
}, 999);

add_action('wp_head', function () {
	$path = ABSPATH . 'wordpress-migration/custom.css';
	if (!file_exists($path)) {
		return;
	}
	echo "<style id=\"as-brand-inline\">\n" . file_get_contents($path) . "\n</style>\n";
}, 100);

/**
 * Prevent wpautop from wrapping section/hero markup in <p> tags.
 */
add_action('init', function () {
	remove_filter('the_content', 'wpautop');
	remove_filter('the_excerpt', 'wpautop');
}, 9);

add_filter('the_content', function ($content) {
	if (is_singular('page') || is_front_page()) {
		return $content;
	}
	return wpautop($content);
}, 12);
