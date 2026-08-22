<?php
/**
 * Fix words truncated by stripping trailing n before closing tags (mission→missio),
 * and ensure About/prose pages keep as-page centering classes.
 * Run: wp eval-file wordpress-migration/fix-truncated-words.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

$admins = get_users(['role' => 'administrator', 'number' => 1]);
if ($admins) {
	wp_set_current_user((int) $admins[0]->ID);
}

$replacements = [
	'missio<' => 'mission<',
	'missio\\u003c' => 'mission\\u003c',
	'missiou003c' => 'missionu003c',
	'celebratio<' => 'celebration<',
	'informatio<' => 'information<',
	'organizatio<' => 'organization<',
	'foundatio<' => 'foundation<',
	'coordinatio<' => 'coordination<',
	'participatio<' => 'participation<',
	'regeneratio<' => 'regeneration<',
	'documentatio<' => 'documentation<',
	'communicatio<' => 'communication<',
	'educatio<' => 'education<',
	'horticulture that people can returo' => 'horticulture that people can return to',
];

$q = new WP_Query([
	'post_type'      => ['page', 'post'],
	'post_status'    => ['publish', 'draft', 'private'],
	'posts_per_page' => -1,
]);

echo "=== Fix truncated words ===\n";

foreach ($q->posts as $post) {
	$c = $post->post_content;
	$fixed = $c;
	foreach ($replacements as $from => $to) {
		$fixed = str_replace($from, $to, $fixed);
	}
	// Generic: word ending in io before tag was likely ion (mission, etc.) — only known list above.

	if ($fixed === $c) {
		continue;
	}
	$save = (strpos($fixed, '<!-- wp:divi/') !== false) ? wp_slash($fixed) : $fixed;
	wp_update_post([
		'ID'           => $post->ID,
		'post_content' => $save,
	]);
	echo "OK {$post->post_name}\n";
}

if (function_exists('wp_cache_flush')) {
	wp_cache_flush();
}

echo "=== Done ===\n";
