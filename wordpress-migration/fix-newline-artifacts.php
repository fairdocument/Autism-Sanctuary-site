<?php
/**
 * Remove literal newline/tab artifacts BETWEEN tags only — never strip "n t" inside words.
 * Run: wp eval-file wordpress-migration/fix-newline-artifacts.php
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

echo "=== Fix newline/tab artifacts (tag-bounded only) ===\n";

foreach ($q->posts as $post) {
	$c = $post->post_content;
	$fixed = as_fix_newline_artifacts($c);
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

function as_fix_newline_artifacts($content) {
	// ONLY between tags — do not globally delete "n t" (breaks "in the", "on the", "return to").
	$content = preg_replace('/(?<=>)n t(?=<)/', "\n", $content);
	$content = preg_replace('/(?<=>)n+(?=<)/', "\n", $content);
	$content = preg_replace('/(?<=>)n(?=[A-Z])/', "\n", $content);
	// Only strip orphan n before closing tags when not part of a word
	$content = preg_replace('/(?<=[^A-Za-z])n+(?=<\/)/', '', $content);
	$content = preg_replace('/(\\\\u003e|u003e)n t(\\\\u003c|u003c)/', '$1\\n$2', $content);
	$content = preg_replace('/(\\\\u003e|u003e)n+(\\\\u003c|u003c)/', '$1\\n$2', $content);
	$content = preg_replace('/(\\\\u003e|u003e)n(?=[A-Z])/', '$1\\n', $content);
	$content = preg_replace('/(?<=[^A-Za-z])n+(?=\\\\u003c\/)/', '', $content);
	$content = preg_replace('/(?<=[^A-Za-z])n+(?=u003c\/)/', '', $content);
	return $content;
}
