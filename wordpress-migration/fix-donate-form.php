<?php
/**
 * Rebuild Donate (and other GF pages) so Gravity Forms shortcodes sit
 * outside Divi Code HTML (ensures scripts enqueue + form visibility).
 * Also used as one-shot repair.
 *
 * Run: wp eval-file wordpress-migration/fix-donate-form.php
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

$targets = [
	'donate'     => 2,
	'contact'    => 1,
	'admissions' => 1,
	'careers'    => 1,
];

// Resolve form IDs by title if needed.
if (class_exists('GFAPI')) {
	foreach (GFAPI::get_forms() as $f) {
		if (stripos($f['title'], 'donat') !== false) {
			$targets['donate'] = (int) $f['id'];
		}
		if (stripos($f['title'], 'inqui') !== false) {
			$targets['contact'] = $targets['admissions'] = $targets['careers'] = (int) $f['id'];
		}
	}
}

echo "=== Fix Gravity Forms in Divi pages ===\n";

foreach ($targets as $slug => $form_id) {
	$page = get_page_by_path($slug);
	if (!$page) {
		echo "Missing /{$slug}/\n";
		continue;
	}

	$html = as_extract_page_html($page->post_content);
	$html = as_clean_page_html($html);
	// Strip any embedded gravityform shortcodes from HTML.
	$html = preg_replace('/\[gravityform[^\]]*\]/i', '', $html);
	$html = trim($html);

	$gf = '[gravityform id="' . (int) $form_id . '" title="false" description="false" ajax="true"]';
	$shortcode = as_html_to_code_sections($html);
	$converted = \ET\Builder\Packages\Conversion\Conversion::maybeConvertContent(
		$shortcode,
		true,
		(int) $page->ID,
		true
	);

	if (!$converted || strpos($converted, '<!-- wp:divi/') === false) {
		echo "FAIL /{$slug}/ convert\n";
		continue;
	}

	// Normalize values then append GF shortcode AFTER Divi blocks so WP enqueues scripts.
	$converted = as_normalize_code_values($converted);
	$converted = rtrim($converted) . "\n\n<!-- wp:shortcode -->\n" . $gf . "\n<!-- /wp:shortcode -->\n";

	wp_update_post([
		'ID'           => $page->ID,
		'post_content' => wp_slash($converted),
	]);
	update_post_meta($page->ID, '_et_pb_use_builder', 'on');
	update_post_meta($page->ID, '_et_pb_page_layout', 'et_no_sidebar');
	update_post_meta($page->ID, '_et_pb_show_title', 'off');

	echo "OK /{$slug}/ form #{$form_id}\n";
}

if (function_exists('wp_cache_flush')) {
	wp_cache_flush();
}

echo "=== Done ===\n";

function as_extract_page_html($content) {
	$parts = [];
	if (preg_match_all('/<!-- wp:divi\/(text|code) (.*?) \/?-->/s', $content, $all, PREG_SET_ORDER)) {
		foreach ($all as $m) {
			if (!preg_match('/"innerContent":\{"desktop":\{"value":"(.*)"\}\}/s', $m[2], $vm)) {
				continue;
			}
			$raw = $vm[1];
			$json = json_decode('"' . str_replace(["\n", "\r"], ['\\n', ''], $raw) . '"');
			$html = is_string($json) ? $json : $raw;
			$html = as_decode_escapes($html);
			$parts[] = $html;
		}
	}
	return trim(implode("\n", $parts));
}

function as_decode_escapes($html) {
	$html = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', static function ($m) {
		return mb_convert_encoding(pack('H*', $m[1]), 'UTF-8', 'UTF-16BE');
	}, $html);
	$html = strtr($html, [
		'u003c' => '<', 'u003e' => '>', 'u0022' => '"', 'u0026' => '&',
		'u003d' => '=', 'u002d' => '-', 'u002f' => '/', 'u002F' => '/',
	]);
	return str_replace(['\\n', '\\"', '\\/'], ["\n", '"', '/'], $html);
}

function as_clean_page_html($html) {
	$html = as_decode_escapes($html);
	$html = preg_replace('/(?<=>)n t(?=<)/', "\n", $html);
	$html = preg_replace('/(?<=>)n+(?=<)/', "\n", $html);
	$html = preg_replace('/(?<=>)n(?=[A-Z])/', "\n", $html);
	$html = preg_replace('/(?<=[^A-Za-z])n+(?=<\/)/', '', $html);
	$html = preg_replace('/(?<=[.!?:])n+(?=<)/', '', $html);
	return trim($html);
}

function as_normalize_code_values($content) {
	return preg_replace_callback(
		'/<!-- wp:divi\/(text|code) (.*?) \/?-->/s',
		static function ($m) {
			$attrs = $m[2];
			$self = substr($m[0], -4) === '/-->' ? ' /-->' : ' -->';
			if (!preg_match('/"innerContent":\{"desktop":\{"value":"(.*)"\}\}/s', $attrs, $vm)) {
				return $m[0];
			}
			$html = as_clean_page_html(as_decode_escapes(json_decode('"' . str_replace(["\n", "\r"], ['\\n', ''], $vm[1]) . '"') ?: $vm[1]));
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

function as_html_to_code_sections($html) {
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
	if (!$chunks) {
		$chunks = [$html];
	}

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
		$body = str_replace(['[/et_pb_code]', '[et_pb_code'], ['&#91;/et_pb_code&#93;', '&#91;et_pb_code'], $chunk);
		$out .= sprintf(
			'[et_pb_section fb_built="1" _builder_version="4.27.4" module_class="%s" custom_padding="0px|0px|0px|0px|false|false" custom_margin="0px|0px|0px|0px|false|false" background_color="RGBA(255,255,255,0)"][et_pb_row _builder_version="4.27.4" custom_padding="0px|0px|0px|0px|false|false" custom_margin="0px|0px|0px|0px|false|false" width="100%%" max_width="100%%"][et_pb_column type="4_4" _builder_version="4.27.4"][et_pb_code _builder_version="4.27.4" module_class="as-preserve"]%s[/et_pb_code][/et_pb_column][/et_pb_row][/et_pb_section]',
			esc_attr($class),
			$body
		);
	}
	return $out;
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
