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

/**
 * Hide classic theme page titles — banners/heroes already provide the H1.
 */
add_filter('et_post_meta_fields_to_remove', function ($fields) {
	$fields[] = 'title';
	return $fields;
});

add_action('wp', function () {
	if (is_page() || is_front_page()) {
		remove_action('et_before_post', 'et_add_post_meta_wrapper', 5);
	}
});

add_filter('body_class', function ($classes) {
	if (is_page() || is_front_page()) {
		$classes[] = 'as-hide-page-title';
	}
	return $classes;
});

/**
 * Blank the classic theme H1 inside the loop (menus/nav still keep titles).
 */
add_filter('the_title', function ($title, $post_id = 0) {
	if (is_admin() || !in_the_loop() || !is_main_query()) {
		return $title;
	}
	if (is_page() || is_front_page()) {
		return '';
	}
	return $title;
}, 10, 2);

/**
 * Soft redirects for retired Admissions and Fellowship pages.
 */
add_action('template_redirect', function () {
	if (is_admin()) {
		return;
	}
	$redirects = get_option('as_path_redirects', []);
	if (!is_array($redirects) || !$redirects) {
		$redirects = [
			'admissions' => home_url('/programs/#interest'),
			'fellowship' => home_url('/careers/'),
		];
	}
	$request = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
	$home_path = trim((string) parse_url(home_url('/'), PHP_URL_PATH), '/');
	if ($home_path && strpos($request, $home_path . '/') === 0) {
		$request = substr($request, strlen($home_path) + 1);
	} elseif ($home_path && $request === $home_path) {
		$request = '';
	}
	$slug = strtolower(explode('/', $request)[0] ?? '');
	if ($slug && isset($redirects[$slug])) {
		wp_safe_redirect($redirects[$slug], 301);
		exit;
	}
});
