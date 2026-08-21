<?php
/**
 * Repair corruption from blanket "n t" removal (in the → ihe, on the → ohe, return to → returo)
 * and leftover literal n between HTML tags.
 *
 * Run: wp eval-file wordpress-migration/repair-text-corruption.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

$admins = get_users(['role' => 'administrator', 'number' => 1]);
if ($admins) {
	wp_set_current_user((int) $admins[0]->ID);
}

// Prefer updated Our Farm copy from HTML backup (has post-migration edits).
$dirs = glob(WP_CONTENT_DIR . '/as-html-backup-*', GLOB_ONLYDIR);
rsort($dirs);
$farm_backup = '';
foreach ($dirs as $dir) {
	$file = $dir . '/our-farm.html';
	if (is_readable($file) && filesize($file) > 500) {
		$farm_backup = as_repair_text(file_get_contents($file));
		echo "Loaded our-farm backup from {$dir}\n";
		break;
	}
}

$q = new WP_Query([
	'post_type'      => ['page', 'post'],
	'post_status'    => ['publish', 'draft', 'private'],
	'posts_per_page' => -1,
]);

echo "=== Repair text corruption ===\n";

foreach ($q->posts as $post) {
	$c = $post->post_content;
	$fixed = $c;

	if ($post->post_name === 'our-farm' && $farm_backup !== '') {
		if (strpos($c, '<!-- wp:divi/') !== false) {
			// Rebuild Divi Code modules from clean HTML.
			$fixed = as_rebuild_divi_from_html($farm_backup, (int) $post->ID);
			if ($fixed === '') {
				echo "FAIL our-farm Divi rebuild\n";
				continue;
			}
		} else {
			$fixed = $farm_backup;
		}
	} else {
		$fixed = as_repair_text($c);
	}

	if ($fixed === $c) {
		continue;
	}

	$save = (strpos($fixed, '<!-- wp:divi/') !== false) ? wp_slash($fixed) : $fixed;
	wp_update_post([
		'ID'           => $post->ID,
		'post_content' => $save,
	]);

	if ($post->post_name === 'our-farm' && strpos($fixed, '<!-- wp:divi/') !== false) {
		update_post_meta($post->ID, '_et_pb_use_builder', 'on');
		update_post_meta($post->ID, '_et_pb_page_layout', 'et_no_sidebar');
		update_post_meta($post->ID, '_et_pb_show_title', 'off');
	}

	echo "OK {$post->post_type} #{$post->ID} {$post->post_name}\n";
}

if (function_exists('wp_cache_flush')) {
	wp_cache_flush();
}

echo "=== Done ===\n";

function as_repair_text($content) {
	// Restore words damaged by global "n t" deletion.
	$map = [
		'ihe '   => 'in the ',
		'ihe'    => 'in the', // end of rare cases — careful: only if whole token?
		'ohe '   => 'on the ',
		'ohe'    => 'on the',
		'returo' => 'return to',
		'ahe '   => 'and the ', // just in case
	];
	// Safer word-boundary style replacements first (with trailing space / punctuation).
	$content = str_replace(
		[
			'ihe ', 'ihe.', 'ihe,', 'ihe;', 'ihe:', 'ihe!', 'ihe?', 'ihe"', "ihe'",
			'ohe ', 'ohe.', 'ohe,', 'ohe;', 'ohe:', 'ohe!', 'ohe?',
			'returo ', 'returo.', 'returo,', 'returo;', 'returo',
		],
		[
			'in the ', 'in the.', 'in the,', 'in the;', 'in the:', 'in the!', 'in the?', 'in the"', "in the'",
			'on the ', 'on the.', 'on the,', 'on the;', 'on the:', 'on the!', 'on the?',
			'return to ', 'return to.', 'return to,', 'return to;', 'return to',
		],
		$content
	);

	// Literal n / n t only BETWEEN tags (not inside words).
	$content = preg_replace('/(?<=>)n t(?=<)/', "\n", $content);
	$content = preg_replace('/(?<=>)n+(?=<)/', "\n", $content);

	// Divi unicode-escaped tag boundaries: \u003e n... \u003c
	$content = preg_replace('/(\\\\u003e|u003e)n t(\\\\u003c|u003c)/', '$1\\n$2', $content);
	$content = preg_replace('/(\\\\u003e|u003e)n+(\\\\u003c|u003c)/', '$1\\n$2', $content);

	// Leading n stuck to a capital letter after a tag close:
	// e.g. </h3>nOur → </h3>\nOur
	$content = preg_replace('/(?<=>)n(?=[A-Z])/', "\n", $content);
	$content = preg_replace('/(\\\\u003e|u003e)n(?=[A-Z])/', '$1\\n', $content);

	// Trailing n before closing tags: "...tables.n</p>" / "...week.nn</"
	$content = preg_replace('/n+(?=<\/)/', '', $content);
	$content = preg_replace('/n+(?=\\\\u003c\/)/', '', $content);
	$content = preg_replace('/n+(?=u003c\/)/', '', $content);

	// "n An individual" after a heading (space after n)
	$content = preg_replace('/(?<=>)n (?=[A-Z])/', "\n", $content);
	$content = preg_replace('/(\\\\u003e|u003e)n (?=[A-Z])/', '$1\\n', $content);

	$content = preg_replace('#https:/(?!/)#', 'https://', $content);
	return $content;
}

function as_rebuild_divi_from_html($html, $post_id) {
	if (!class_exists('\\ET\\Builder\\Packages\\Conversion\\Conversion')) {
		return '';
	}
	\ET\Builder\Packages\Conversion\Conversion::initialize_shortcode_framework();

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

	$converted = \ET\Builder\Packages\Conversion\Conversion::maybeConvertContent($out, true, $post_id, true);
	if (!$converted || strpos($converted, '<!-- wp:divi/') === false) {
		return '';
	}
	return $converted;
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
