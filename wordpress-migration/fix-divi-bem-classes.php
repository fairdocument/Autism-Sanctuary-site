<?php
/**
 * Fix Divi-corrupted BEM modifiers (u002d → -) and rebuild layout CSS hooks.
 * Run: wp eval-file wordpress-migration/fix-divi-bem-classes.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

$admins = get_users(['role' => 'administrator', 'number' => 1]);
if ($admins) {
	wp_set_current_user((int) $admins[0]->ID);
}

$q = new WP_Query([
	'post_type'      => ['page', 'post', 'et_body_layout'],
	'post_status'    => ['publish', 'draft', 'private'],
	'posts_per_page' => -1,
]);

echo "=== Fix Divi BEM / unicode class corruption ===\n";

foreach ($q->posts as $post) {
	$c = $post->post_content;
	$fixed = as_fix_bem_corruption($c);
	if ($fixed === $c) {
		continue;
	}
	$save = (strpos($fixed, '<!-- wp:divi/') !== false) ? wp_slash($fixed) : $fixed;
	wp_update_post([
		'ID'           => $post->ID,
		'post_content' => $save,
	]);
	echo "OK {$post->post_type} #{$post->ID} {$post->post_name}\n";
}

if (function_exists('wp_cache_flush')) {
	wp_cache_flush();
}

echo "=== Done ===\n";

function as_fix_bem_corruption($content) {
	// Proper JSON unicode hyphens first, then bare leftovers from stripped backslashes.
	$content = str_replace(
		['\\u002d\\u002d', '\\u002d', 'u002du002d', 'u002d'],
		['--', '-', '--', '-'],
		$content
	);

	// Tag-bounded leftover n artifacts only.
	$content = preg_replace('/(?<=>)n t(?=<)/', "\n", $content);
	$content = preg_replace('/(?<=>)n+(?=<)/', "\n", $content);
	$content = preg_replace('/(?<=>)n(?=[A-Z])/', "\n", $content);
	$content = preg_replace('/n+(?=<\/)/', '', $content);
	$content = preg_replace('/(\\\\u003e|u003e)n+(?=\\\\u003c|u003c)/', '$1\\n', $content);
	$content = preg_replace('/(\\\\u003e|u003e)n(?=[A-Z])/', '$1\\n', $content);
	$content = preg_replace('/n+(?=\\\\u003c\/)/', '', $content);
	$content = preg_replace('/n+(?=u003c\/)/', '', $content);

	return $content;
}
