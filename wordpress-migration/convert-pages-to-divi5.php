<?php
/**
 * Convert marketing pages to Divi 5 layouts while preserving HTML/CSS visuals.
 * Run: wp eval-file wordpress-migration/convert-pages-to-divi5.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

if (!class_exists('\\ET\\Builder\\Packages\\Conversion\\Conversion')) {
	echo "Divi 5 Conversion API not available.\n";
	return;
}

$admins = get_users(['role' => 'administrator', 'number' => 1]);
if ($admins) {
	wp_set_current_user((int) $admins[0]->ID);
}

\ET\Builder\Packages\Conversion\Conversion::initialize_shortcode_framework();

$skip = ['news']; // already native Divi 5 Blog layout
$pages = get_posts([
	'post_type'      => 'page',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
]);

echo "=== Convert pages to Divi 5 ===\n";

foreach ($pages as $page) {
	if (in_array($page->post_name, $skip, true)) {
		echo "Skip /{$page->post_name}/ (already managed)\n";
		continue;
	}

	$html = $page->post_content;

	// Already a proper multi-module Divi 5 page (e.g. News). About may be a single wrapped text — rebuild for consistency.
	$is_raw_html = (strpos($html, '[et_pb_') === false && strpos($html, '<!-- wp:divi/') === false);
	$is_thin_wrap = (strpos($html, '<!-- wp:divi/') !== false && strpos($html, 'as-banner') !== false && substr_count($html, 'wp:divi/section') <= 2);

	if (!$is_raw_html && !$is_thin_wrap) {
		echo "Skip /{$page->post_name}/ (already Divi structured)\n";
		continue;
	}

	// If thin Divi wrap, extract inner HTML from text module values.
	if ($is_thin_wrap) {
		$extracted = as_extract_html_from_divi5($html);
		if ($extracted) {
			$html = $extracted;
		}
	}

	$html = as_fix_media_urls($html);
	$shortcode = as_html_to_divi4_sections($html);

	$converted = \ET\Builder\Packages\Conversion\Conversion::maybeConvertContent(
		$shortcode,
		true,
		(int) $page->ID,
		true
	);

	if (!$converted || strpos($converted, '<!-- wp:divi/') === false) {
		echo "FAIL /{$page->post_name}/ conversion\n";
		continue;
	}

	wp_update_post([
		'ID'           => $page->ID,
		'post_content' => wp_slash($converted),
	]);
	update_post_meta($page->ID, '_et_pb_use_builder', 'on');
	update_post_meta($page->ID, '_et_pb_page_layout', 'et_no_sidebar');
	update_post_meta($page->ID, '_et_pb_show_title', 'off');
	update_post_meta($page->ID, '_et_builder_version', '5.0.0');

	echo "OK /{$page->post_name}/ (#{$page->ID}) sections≈" . substr_count($converted, 'wp:divi/section') . "\n";
}

echo "=== Done ===\n";

/**
 * Fix broken https:/host URLs (missing slash).
 */
function as_fix_media_urls($html) {
	return preg_replace('#https:/(?!/)#', 'https://', $html);
}

/**
 * Pull concatenated text-module HTML out of a thin Divi 5 wrap.
 */
function as_extract_html_from_divi5($content) {
	$parts = [];
	if (!preg_match_all('/"innerContent"\s*:\s*\{\s*"desktop"\s*:\s*\{\s*"value"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $content, $m)) {
		return '';
	}
	foreach ($m[1] as $encoded) {
		$json = json_decode('"' . $encoded . '"');
		if (!is_string($json)) {
			// Fallback: bare unicode escapes used by Divi
			$json = $encoded;
			$json = strtr($json, [
				'u003c' => '<',
				'u003e' => '>',
				'u0022' => '"',
				'u0026' => '&',
				'u003C' => '<',
				'u003E' => '>',
			]);
			$json = str_replace(['\\n', "\n"], ["\n", "\n"], $json);
		}
		$parts[] = $json;
	}
	return trim(implode("\n", $parts));
}

/**
 * Turn page HTML into Divi 4 shortcodes (one section per as-* block), then convert to D5.
 */
function as_html_to_divi4_sections($html) {
	$html = trim($html);
	if ($html === '') {
		$html = '<p></p>';
	}

	$chunks = as_split_page_chunks($html);
	$out = '';

	foreach ($chunks as $i => $chunk) {
		$chunk = trim($chunk);
		if ($chunk === '') {
			continue;
		}

		$class = 'as-divi-chunk';
		if (preg_match('/^<section\b[^>]*class="([^"]*)"/i', $chunk, $m)) {
			$class = trim($m[1] . ' as-divi-chunk');
		} elseif (preg_match('/^<div\b[^>]*class="([^"]*as-(?:page|prose|contact)[^"]*)"/i', $chunk, $m)) {
			$class = trim($m[1] . ' as-divi-chunk');
		}

		$is_hero = (strpos($class, 'as-hero') !== false);
		$row_width = $is_hero ? '100%' : '100%';
		$row_max = $is_hero ? '100%' : '100%';

		// Escape closing shortcode sequences inside HTML.
		$body = str_replace(['[/et_pb_code]', '[et_pb_code'], ['&#91;/et_pb_code&#93;', '&#91;et_pb_code'], $chunk);

		$out .= sprintf(
			'[et_pb_section fb_built="1" _builder_version="4.27.4" module_class="%s" custom_padding="0px|0px|0px|0px|false|false" custom_margin="0px|0px|0px|0px|false|false" background_color="RGBA(255,255,255,0)"][et_pb_row _builder_version="4.27.4" custom_padding="0px|0px|0px|0px|false|false" custom_margin="0px|0px|0px|0px|false|false" width="%s" max_width="%s"][et_pb_column type="4_4" _builder_version="4.27.4"][et_pb_code _builder_version="4.27.4" module_class="as-preserve"]%s[/et_pb_code][/et_pb_column][/et_pb_row][/et_pb_section]',
			esc_attr($class),
			$row_width,
			$row_max,
			$body
		);
	}

	return $out;
}

/**
 * Split page HTML into top-level section/banner/prose chunks.
 */
function as_split_page_chunks($html) {
	$chunks = [];
	$offset = 0;
	$length = strlen($html);

	while ($offset < $length) {
		if (!preg_match('/<(section|div)\b[^>]*class="[^"]*\b(as-hero|as-section|as-banner|as-page|as-prose|as-contact-grid)\b[^"]*"[^>]*>/i', $html, $m, PREG_OFFSET_CAPTURE, $offset)) {
			$rest = trim(substr($html, $offset));
			if ($rest !== '') {
				$chunks[] = $rest;
			}
			break;
		}

		$start = $m[0][1];
		$tag = strtolower($m[1][0]);

		if ($start > $offset) {
			$before = trim(substr($html, $offset, $start - $offset));
			if ($before !== '') {
				$chunks[] = $before;
			}
		}

		$end = as_find_matching_close_tag($html, $tag, $start);
		if ($end === null) {
			$chunks[] = substr($html, $start);
			break;
		}

		$chunks[] = substr($html, $start, $end - $start);
		$offset = $end;
	}

	return $chunks ?: [$html];
}

function as_find_matching_close_tag($html, $tag, $start) {
	$open = '/<' . $tag . '\b[^>]*>/i';
	$close = '/<\/' . $tag . '\s*>/i';
	$pos = $start;
	$depth = 0;
	$len = strlen($html);

	while ($pos < $len) {
		$next_open = preg_match($open, $html, $om, PREG_OFFSET_CAPTURE, $pos) ? $om[0][1] : null;
		$next_close = preg_match($close, $html, $cm, PREG_OFFSET_CAPTURE, $pos) ? $cm[0][1] : null;

		if ($next_close === null) {
			return null;
		}

		if ($next_open !== null && $next_open < $next_close) {
			$depth++;
			$pos = $next_open + strlen($om[0][0]);
			continue;
		}

		$depth--;
		$pos = $next_close + strlen($cm[0][0]);
		if ($depth === 0) {
			return $pos;
		}
	}

	return null;
}
