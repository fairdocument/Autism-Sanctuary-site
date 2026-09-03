<?php
/**
 * Normalize Divi row/module widths on marketing pages (brand column).
 */

if (!defined('ABSPATH')) {
	exit(1);
}

$ids = [27, 28, 31, 32, 34, 36, 37, 38, 39, 409, 416, 419];

foreach ($ids as $id) {
	$post = get_post($id);
	if (!$post) {
		echo "MISS $id\n";
		continue;
	}
	$content = $post->post_content;
	$before = $content;
	$content = str_replace('"maxWidth":"1440px"', '"maxWidth":"80rem"', $content);
	$content = str_replace('"width":"700px"', '"width":"100%"', $content);
	$content = str_replace('"width":"500px"', '"width":"100%"', $content);
	if ($content === $before) {
		echo "SKIP $id (no width changes)\n";
		continue;
	}
	wp_update_post([
		'ID'           => $id,
		'post_content' => wp_slash($content),
	]);
	echo "OK $id {$post->post_name}\n";
}

echo "DONE\n";
