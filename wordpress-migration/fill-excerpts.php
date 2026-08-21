<?php
/**
 * Fill post excerpts from Divi 5 encoded HTML fragments.
 * Run: wp eval-file wordpress-migration/fill-excerpts.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

$posts = get_posts([
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'posts_per_page' => 100,
]);

foreach ($posts as $post) {
	$text = as_extract_plain_text($post->post_content);
	if (strlen($text) < 40) {
		echo "Skip #{$post->ID} len=" . strlen($text) . "\n";
		continue;
	}
	$excerpt = wp_html_excerpt($text, 180, '…');
	wp_update_post([
		'ID'           => $post->ID,
		'post_excerpt' => $excerpt,
	]);
	echo "#{$post->ID} {$excerpt}\n";
}

function as_extract_plain_text($content) {
	$c = (string) $content;

	// Divi 5 often stores HTML entities as bare unicode escapes without backslash.
	$c = strtr($c, [
		'u003c' => '<',
		'u003e' => '>',
		'u0022' => '"',
		'u0026' => '&',
		'u003C' => '<',
		'u003E' => '>',
	]);

	// Also handle classic \uXXXX if present.
	$c = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', static function ($m) {
		$code = hexdec($m[1]);
		return mb_convert_encoding(pack('n', $code), 'UTF-8', 'UTF-16BE');
	}, $c);

	$c = str_replace(['\\n', '\\/', '\\"'], ["\n", '/', '"'], $c);

	if (preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $c, $paras)) {
		$joined = implode(' ', array_map('wp_strip_all_tags', $paras[1]));
		$joined = html_entity_decode($joined, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$joined = preg_replace('/\s+/', ' ', $joined);
		$joined = trim($joined);
		if (strlen($joined) >= 40) {
			return $joined;
		}
	}

	$c = wp_strip_all_tags($c);
	$c = html_entity_decode($c, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	$c = preg_replace('/\s+/', ' ', $c);
	return trim($c);
}
