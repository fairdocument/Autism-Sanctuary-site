<?php
/**
 * Convert plain HTML pages (Divi builder off) to HTML-in-Divi-Code modules.
 * Skips pages already using the Divi builder.
 *
 * Run: wp eval-file wordpress-migration/convert-plain-html-to-divi.php
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

$backup_dir = WP_CONTENT_DIR . '/as-plain-html-backup-' . gmdate('Ymd-His');
wp_mkdir_p($backup_dir);
echo "Backup: {$backup_dir}\n";

$pages = get_posts([
	'post_type'      => 'page',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'orderby'        => 'menu_order title',
	'order'          => 'ASC',
]);

echo "=== Convert plain HTML pages to Divi Code ===\n";

$converted_count = 0;
$skipped_count = 0;

foreach ($pages as $page) {
	$slug = $page->post_name;
	$raw = $page->post_content;
	$builder = get_post_meta($page->ID, '_et_pb_use_builder', true);
	$has_divi = (strpos($raw, '<!-- wp:divi/') !== false || strpos($raw, '[et_pb_') !== false);

	if ($builder === 'on' || $has_divi) {
		echo "Skip /{$slug}/ (already Divi)\n";
		$skipped_count++;
		continue;
	}

	$html = as_plain_clean_html($raw);

	file_put_contents("{$backup_dir}/{$slug}.html", $html);
	file_put_contents("{$backup_dir}/{$slug}.raw.txt", $raw);

	if (strlen($html) < 40) {
		echo "FAIL /{$slug}/ empty HTML\n";
		continue;
	}

	$shortcode = as_plain_html_to_code_shortcode($html);
	$converted = \ET\Builder\Packages\Conversion\Conversion::maybeConvertContent(
		$shortcode,
		true,
		(int) $page->ID,
		true
	);

	if (!$converted || strpos($converted, '<!-- wp:divi/') === false) {
		echo "FAIL /{$slug}/ conversion\n";
		continue;
	}

	if (!as_plain_divi_parses_html($converted)) {
		echo "FAIL /{$slug}/ parse empty before save\n";
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

	$saved = get_post_field('post_content', $page->ID);
	$ok = as_plain_divi_parses_html($saved);
	echo ($ok ? 'OK' : 'WARN') . " /{$slug}/ (#{$page->ID}) html=" . strlen($html)
		. ' codes=' . substr_count($saved, 'wp:divi/code') . "\n";
	$converted_count++;
}

if (function_exists('wp_cache_flush')) {
	wp_cache_flush();
}

echo "=== Done: {$converted_count} converted, {$skipped_count} skipped ===\n";

function as_plain_clean_html($html) {
	$html = trim((string) $html);
	$html = preg_replace('/(?<=>)n t(?=<)/', "\n", $html);
	$html = preg_replace('/(?<=>)n+(?=<)/', "\n", $html);
	$html = preg_replace('/(?<=>)n(?=[A-Z])/', "\n", $html);
	$html = preg_replace('/n+(?=<\/)/', '', $html);
	$html = preg_replace('#https:/(?!/)#', 'https://', $html);
	$html = str_replace(
		['ihe ', 'ohe ', 'returo'],
		['in the ', 'on the ', 'return to'],
		$html
	);
	return trim($html);
}

function as_plain_html_to_code_shortcode($html) {
	$chunks = as_plain_split_chunks($html);
	$out = '';
	foreach ($chunks as $chunk) {
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
		$body = str_replace(
			['[/et_pb_code]', '[et_pb_code'],
			['&#91;/et_pb_code&#93;', '&#91;et_pb_code'],
			$chunk
		);
		$out .= sprintf(
			'[et_pb_section fb_built="1" _builder_version="4.27.4" module_class="%s" custom_padding="0px|0px|0px|0px|false|false" custom_margin="0px|0px|0px|0px|false|false" background_color="RGBA(255,255,255,0)"][et_pb_row _builder_version="4.27.4" custom_padding="0px|0px|0px|0px|false|false" custom_margin="0px|0px|0px|0px|false|false" width="100%%" max_width="100%%"][et_pb_column type="4_4" _builder_version="4.27.4"][et_pb_code _builder_version="4.27.4" module_class="as-preserve"]%s[/et_pb_code][/et_pb_column][/et_pb_row][/et_pb_section]',
			esc_attr($class),
			$body
		);
	}
	return $out;
}

function as_plain_split_chunks($html) {
	$chunks = [];
	$offset = 0;
	$len = strlen($html);
	while ($offset < $len) {
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
		$end = as_plain_match_close($html, $tag, $start);
		if ($end === null) {
			$chunks[] = substr($html, $start);
			break;
		}
		$chunks[] = substr($html, $start, $end - $start);
		$offset = $end;
	}
	return $chunks ?: [$html];
}

function as_plain_match_close($html, $tag, $start) {
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

function as_plain_divi_parses_html($content) {
	foreach (parse_blocks($content) as $b) {
		if (as_plain_block_has_html($b)) {
			return true;
		}
	}
	return false;
}

function as_plain_block_has_html($b) {
	$val = $b['attrs']['content']['innerContent']['desktop']['value'] ?? '';
	if (is_string($val) && strpos($val, '<') !== false && strpos($val, 'u003c') === false) {
		return true;
	}
	foreach ($b['innerBlocks'] ?? [] as $inner) {
		if (as_plain_block_has_html($inner)) {
			return true;
		}
	}
	return false;
}
