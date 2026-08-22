<?php
/**
 * Fix exposed \u003c… escapes and stray n artifacts in Divi Code modules.
 * Rebuilds every page from decoded/cleaned HTML.
 *
 * Run: wp eval-file wordpress-migration/fix-exposed-escapes.php
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

$dirs = glob(WP_CONTENT_DIR . '/as-html-backup-*', GLOB_ONLYDIR);
rsort($dirs);

$pages = get_posts([
	'post_type'      => 'page',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
]);

echo "=== Fix exposed escapes + rebuild Divi ===\n";

foreach ($pages as $page) {
	$slug = $page->post_name;
	$html = '';

	// Prefer newest backup HTML when available and clean.
	foreach ($dirs as $dir) {
		$file = "{$dir}/{$slug}.html";
		if (is_readable($file) && filesize($file) > 40) {
			$candidate = file_get_contents($file);
			if (strpos($candidate, 'u003c') === false && strpos($candidate, '\\u003c') === false) {
				$html = $candidate;
				break;
			}
		}
	}

	if ($html === '') {
		$html = as_extract_html($page->post_content);
	}

	$html = as_fully_clean_html($html);
	if (strlen($html) < 40) {
		echo "FAIL /{$slug}/ empty\n";
		continue;
	}

	$blog = '';
	if ($slug === 'news') {
		$raw = $page->post_content;
		if (preg_match('/<!-- wp:divi\/blog .*?\/-->/s', $raw, $m)) {
			$blog = $m[0];
		}
	}

	$shortcode = as_html_to_code_shortcode($html);
	$converted = \ET\Builder\Packages\Conversion\Conversion::maybeConvertContent(
		$shortcode,
		true,
		(int) $page->ID,
		true
	);

	if (!$converted || strpos($converted, '<!-- wp:divi/code') === false) {
		echo "FAIL /{$slug}/ convert\n";
		continue;
	}

	// Decode any mixed escapes Conversion left inside values before save.
	$converted = as_normalize_divi_values($converted);
	if ($blog) {
		$converted .= "\n" . $blog;
	}

	wp_update_post([
		'ID'           => $page->ID,
		'post_content' => wp_slash($converted),
	]);
	update_post_meta($page->ID, '_et_pb_use_builder', 'on');
	update_post_meta($page->ID, '_et_pb_page_layout', 'et_no_sidebar');
	update_post_meta($page->ID, '_et_pb_show_title', 'off');

	$saved = get_post_field('post_content', $page->ID);
	$val = as_first_code_value($saved);
	$bad = is_string($val) && (strpos($val, '\\u003c') !== false || (strpos($val, 'u003c') !== false && strpos($val, '<') !== false));
	echo ($bad ? 'WARN' : 'OK') . " /{$slug}/ html=" . strlen($html) . " end=" . substr(preg_replace('/\s+/', ' ', $val), -60) . "\n";
}

if (function_exists('wp_cache_flush')) {
	wp_cache_flush();
}

echo "=== Done ===\n";

function as_extract_html($content) {
	$parts = [];
	if (preg_match_all('/<!-- wp:divi\/(text|code) (.*?) \/?-->/s', $content, $all, PREG_SET_ORDER)) {
		foreach ($all as $m) {
			if (!preg_match('/"innerContent":\{"desktop":\{"value":"(.*)"\}\}/s', $m[2], $vm)) {
				continue;
			}
			$parts[] = as_decode_value($vm[1]);
		}
	}
	if (!$parts && strpos($content, '<!-- wp:divi/') === false) {
		return $content;
	}
	return trim(implode("\n", $parts));
}

function as_decode_value($raw) {
	$json = json_decode('"' . str_replace(["\n", "\r"], ['\\n', ''], $raw) . '"');
	$html = is_string($json) ? $json : $raw;
	return as_decode_unicode_escapes($html);
}

function as_decode_unicode_escapes($html) {
	// JSON-style and bare leftovers.
	$html = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', static function ($m) {
		return mb_convert_encoding(pack('H*', $m[1]), 'UTF-8', 'UTF-16BE');
	}, $html);
	$html = strtr($html, [
		'u003c' => '<', 'u003e' => '>', 'u003C' => '<', 'u003E' => '>',
		'u0022' => '"', 'u0026' => '&', 'u003d' => '=', 'u0027' => "'",
		'u002d' => '-', 'u002f' => '/', 'u002F' => '/',
	]);
	$html = str_replace(['\\n', '\\"', '\\/', '\\<', '\\>'], ["\n", '"', '/', '<', '>'], $html);
	return $html;
}

function as_fully_clean_html($html) {
	$html = as_decode_unicode_escapes((string) $html);

	// Restore known truncated words.
	$html = str_replace(
		[
			'missio<', 'celebratio<', 'informatio<', 'organizatio<', 'foundatio<',
			'coordinatio<', 'participatio<', 'documentatio<', 'communicatio<', 'educatio<',
		],
		[
			'mission<', 'celebration<', 'information<', 'organization<', 'foundation<',
			'coordination<', 'participation<', 'documentation<', 'communication<', 'education<',
		],
		$html
	);
	$html = str_replace(['ihe ', 'ohe ', 'returo'], ['in the ', 'on the ', 'return to'], $html);

	// Tag-bounded n / n t only (never inside words).
	$html = preg_replace('/(?<=>)n t(?=<)/', "\n", $html);
	$html = preg_replace('/(?<=>)n+(?=<)/', "\n", $html);
	$html = preg_replace('/(?<=>)n(?=[A-Z])/', "\n", $html);
	$html = preg_replace('/(?<=>)n (?=[A-Z])/', "\n", $html);
	$html = preg_replace('/(?<=[^A-Za-z])n+(?=<\/)/', '', $html);
	// Orphan n after sentence punctuation before tag/end
	$html = preg_replace('/(?<=[.!?:])n+(?=<)/', '', $html);
	$html = preg_replace('/(?<=[.!?:])nn(?=[A-Z])/', ' ', $html);

	$html = preg_replace('#https:/(?!/)#', 'https://', $html);
	return trim($html);
}

function as_normalize_divi_values($content) {
	return preg_replace_callback(
		'/<!-- wp:divi\/(text|code) (.*?) \/?-->/s',
		static function ($m) {
			$attrs = $m[2];
			$self = substr($m[0], -4) === '/-->' ? ' /-->' : ' -->';
			if (!preg_match('/"innerContent":\{"desktop":\{"value":"(.*)"\}\}/s', $attrs, $vm)) {
				return $m[0];
			}
			$html = as_fully_clean_html(as_decode_value($vm[1]));
			// Store as normal JSON string (real < > with escaped quotes) — wp_slash on save.
			$enc = substr(json_encode($html, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 1, -1);
			$attrs2 = preg_replace(
				'/"innerContent":\{"desktop":\{"value":"(.*)"\}\}/s',
				'"innerContent":{"desktop":{"value":"' . $enc . '"}}',
				$attrs,
				1
			);
			return '<!-- wp:divi/' . $m[1] . ' ' . $attrs2 . $self;
		},
		$content
	);
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

function as_split_chunks($html) {
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
