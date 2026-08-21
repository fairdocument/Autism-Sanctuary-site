<?php
/**
 * Convert Programs + Our Farm to Divi 5 Code modules (safe encoding).
 * Uses current clean HTML (or backup) and saves with wp_slash.
 *
 * Run: wp eval-file wordpress-migration/divi-fix-programs-farm.php
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

$slugs = ['programs', 'our-farm'];

echo "=== Divi Code convert: programs + our-farm ===\n";

foreach ($slugs as $slug) {
	$page = get_page_by_path($slug);
	if (!$page) {
		echo "Missing /{$slug}/\n";
		continue;
	}

	$html = $page->post_content;

	// If still Divi/garbled, prefer backup then decode.
	if (strpos($html, 'wp:divi/') !== false || strpos($html, 'u003c') !== false) {
		$dirs = glob(WP_CONTENT_DIR . '/as-html-backup-*', GLOB_ONLYDIR);
		rsort($dirs);
		$file = ($dirs[0] ?? '') . "/{$slug}.html";
		if (is_readable($file)) {
			$html = file_get_contents($file);
			echo "{$slug}: loaded backup HTML\n";
		}
	}

	$html = as_clean_html($html);
	if (strlen($html) < 80) {
		echo "FAIL /{$slug}/ empty after clean\n";
		continue;
	}

	$shortcode = as_html_to_code_shortcode($html);
	$converted = \ET\Builder\Packages\Conversion\Conversion::maybeConvertContent(
		$shortcode,
		true,
		(int) $page->ID,
		true
	);

	if (!$converted || strpos($converted, '<!-- wp:divi/code') === false) {
		echo "FAIL /{$slug}/ conversion\n";
		continue;
	}

	// Verify parse_blocks sees real HTML before save.
	if (!as_divi_has_html($converted)) {
		echo "FAIL /{$slug}/ parse_blocks empty before save\n";
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

	// Re-read and verify escapes survived save.
	$saved = get_post_field('post_content', $page->ID);
	$ok = as_divi_has_html($saved);
	$val = as_first_code_value($saved);
	$garbled = is_string($val) && strpos($val, 'u003c') !== false && strpos($val, '<') === false;
	echo ($ok && !$garbled ? 'OK' : 'WARN') . " /{$slug}/ (#{$page->ID}) codes=" . substr_count($saved, 'wp:divi/code')
		. ' html_len=' . strlen($html)
		. ' val_start=' . substr($val, 0, 50) . "\n";
}

if (function_exists('wp_cache_flush')) {
	wp_cache_flush();
}

echo "=== Done ===\n";

function as_clean_html($html) {
	$html = (string) $html;
	$html = preg_replace('/(?<=>)n+(?=<)/', "\n", $html);
	if (strpos($html, 'u003c') !== false) {
		$html = strtr($html, [
			'u003c' => '<', 'u003e' => '>', 'u003C' => '<', 'u003E' => '>',
			'u0022' => '"', 'u0026' => '&', 'u003d' => '=', 'u0027' => "'",
		]);
	}
	$html = str_replace(['\\n', '\\"', '\\/', '\\<', '\\>'], ["\n", '"', '/', '<', '>'], $html);
	$html = preg_replace('#https:/(?!/)#', 'https://', $html);
	return trim($html);
}

function as_html_to_code_shortcode($html) {
	$chunks = as_split_chunks($html);
	$out = '';
	foreach ($chunks as $chunk) {
		$chunk = trim($chunk);
		if ($chunk === '') {
			continue;
		}
		$class = 'as-divi-chunk';
		if (preg_match('/^<section\b[^>]*class="([^"]*)"/i', $chunk, $m)) {
			$class = trim($m[1] . ' as-divi-chunk');
		} elseif (preg_match('/^<div\b[^>]*class="([^"]*as-(?:page|prose)[^"]*)"/i', $chunk, $m)) {
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

function as_split_chunks($html) {
	$chunks = [];
	$offset = 0;
	$len = strlen($html);
	while ($offset < $len) {
		if (!preg_match('/<(section|div)\b[^>]*class="[^"]*\b(as-hero|as-section|as-banner|as-page|as-prose)\b[^"]*"[^>]*>/i', $html, $m, PREG_OFFSET_CAPTURE, $offset)) {
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
		$end = as_match_close($html, $tag, $start);
		if ($end === null) {
			$chunks[] = substr($html, $start);
			break;
		}
		$chunks[] = substr($html, $start, $end - $start);
		$offset = $end;
	}
	return $chunks ?: [$html];
}

function as_match_close($html, $tag, $start) {
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

function as_divi_has_html($content) {
	foreach (parse_blocks($content) as $b) {
		if (as_block_has_html($b)) {
			return true;
		}
	}
	return false;
}

function as_block_has_html($b) {
	$val = $b['attrs']['content']['innerContent']['desktop']['value'] ?? '';
	if (is_string($val) && strpos($val, '<') !== false && strpos($val, 'u003c') === false) {
		return true;
	}
	foreach ($b['innerBlocks'] ?? [] as $inner) {
		if (as_block_has_html($inner)) {
			return true;
		}
	}
	return false;
}

function as_first_code_value($content) {
	$blocks = parse_blocks($content);
	$walk = function ($blocks) use (&$walk) {
		foreach ($blocks as $b) {
			if (($b['blockName'] ?? '') === 'divi/code') {
				return $b['attrs']['content']['innerContent']['desktop']['value'] ?? '';
			}
			$v = $walk($b['innerBlocks'] ?? []);
			if ($v !== '') {
				return $v;
			}
		}
		return '';
	};
	return $walk($blocks);
}
