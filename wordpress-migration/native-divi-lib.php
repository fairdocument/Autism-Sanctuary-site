<?php
/**
 * Shared helpers: extract brand HTML → native Divi 4 shortcodes → Divi 5 blocks.
 */

if (!defined('ABSPATH')) {
	return;
}

function as_native_text_style() {
	return 'text_font="Source Sans 3||||||||" text_text_color="#4A534C" header_font="Cormorant Garamond||||||||" header_text_color="#1E3D2C" custom_margin="||0px||false|false" custom_padding="0px|0px|0px|0px|false|false"';
}

function as_native_media_map() {
	static $map = null;
	if ($map !== null) {
		return $map;
	}
	$map = [];
	$q = new WP_Query([
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => 200,
		'fields'         => 'ids',
	]);
	foreach ($q->posts as $id) {
		$file = basename(get_attached_file($id));
		$map[$file] = wp_get_attachment_url($id);
	}
	return $map;
}

function as_native_extract_html($content) {
	if (strpos($content, '<!-- wp:divi/') === false && strpos($content, '[et_pb_') === false) {
		return trim($content);
	}
	$parts = [];
	if (preg_match_all('/<!-- wp:divi\/(text|code) (.*?) \/?-->/s', $content, $all, PREG_SET_ORDER)) {
		foreach ($all as $m) {
			if (!preg_match('/"innerContent":\{"desktop":\{"value":"(.*)"\}\}/s', $m[2], $vm)) {
				continue;
			}
			$parts[] = as_native_decode_value($vm[1]);
		}
	}
	if (preg_match_all('/<!-- wp:shortcode -->\s*(.*?)\s*<!-- \/wp:shortcode -->/s', $content, $shortcodes)) {
		foreach ($shortcodes[1] as $sc) {
			$sc = trim($sc);
			if ($sc !== '') {
				$parts[] = $sc;
			}
		}
	}
	if ($parts) {
		return trim(implode("\n", $parts));
	}
	if (preg_match_all('/\[et_pb_code[^\]]*\](.*?)\[\/et_pb_code\]/s', $content, $legacy)) {
		return trim(implode("\n", $legacy[1]));
	}
	return trim($content);
}

function as_native_decode_value($raw) {
	$json = json_decode('"' . $raw . '"');
	if (is_string($json)) {
		return $json;
	}
	$html = $raw;
	if (strpos($html, 'u003c') !== false) {
		$html = strtr($html, [
			'u003c' => '<', 'u003e' => '>', 'u003C' => '<', 'u003E' => '>',
			'u0022' => '"', 'u0026' => '&', 'u003d' => '=', 'u0027' => "'",
		]);
	}
	return str_replace(['\\n', '\\"', '\\/', '\\<', '\\>'], ["\n", '"', '/', '<', '>'], $html);
}

function as_native_fix_typos($html) {
	$html = (string) $html;
	$html = preg_replace('/(?<=>)n t(?=<)/', "\n", $html);
	$html = preg_replace('/(?<=>)n+(?=<)/', "\n", $html);
	$html = preg_replace('/(?<=>)n(?=[A-Z])/', "\n", $html);
	$html = preg_replace('/n+(?=<\/)/', '', $html);
	$html = preg_replace('#https:/(?!/)#', 'https://', $html);
	$html = str_replace(
		[
			'<h2>Missio</h2>', '<h2>Visio</h2>',
			'Thank you to Frances', 'ihe ', 'ohe ', 'returo',
		],
		[
			'<h2>Mission</h2>', '<h2>Vision</h2>',
			'Thanks to Frances', 'in the ', 'on the ', 'return to',
		],
		$html
	);
	return trim($html);
}

function as_native_append_forms($html, $slug) {
	if (strpos($html, 'gravityform') !== false || !class_exists('GFAPI')) {
		return $html;
	}
	$form_id = 0;
	foreach (GFAPI::get_forms() as $form) {
		$title = strtolower($form['title'] ?? '');
		if ($slug === 'donate' && strpos($title, 'donat') !== false) {
			$form_id = (int) $form['id'];
			break;
		}
		if ($slug !== 'donate' && strpos($title, 'inqui') !== false) {
			$form_id = (int) $form['id'];
			break;
		}
	}
	if (!$form_id) {
		return $html;
	}
	$shortcode = '[gravityform id="' . $form_id . '" title="false" description="false" ajax="true"]';
	if ($slug === 'contact' && strpos($html, 'Send an inquiry') !== false) {
		return preg_replace('/(<h2>Send an inquiry<\/h2>)/', '$1' . "\n" . $shortcode, $html, 1);
	}
	if (in_array($slug, ['careers', 'donate', 'admissions'], true)) {
		return $html . "\n" . $shortcode;
	}
	return $html;
}

function as_native_split_top_level($html) {
	$chunks = [];
	$offset = 0;
	$len = strlen($html);
	$pattern = '/<(section|div)\b[^>]*class="[^"]*\b(as-hero|as-banner|as-section|as-page as-prose|as-contact-grid)\b[^"]*"[^>]*>/i';

	while ($offset < $len) {
		if (!preg_match($pattern, $html, $m, PREG_OFFSET_CAPTURE, $offset)) {
			$rest = trim(substr($html, $offset));
			if ($rest !== '') {
				$chunks[] = $rest;
			}
			break;
		}
		$start = $m[0][1];
		if ($start > $offset) {
			$before = trim(substr($html, $offset, $start - $offset));
			if ($before !== '') {
				$chunks[] = $before;
			}
		}
		$tag = strtolower($m[1][0]);
		$end = as_native_match_close($html, $tag, $start);
		if ($end === null) {
			$chunks[] = substr($html, $start);
			break;
		}
		$chunks[] = substr($html, $start, $end - $start);
		$offset = $end;
	}
	return $chunks ?: [$html];
}

function as_native_match_close($html, $tag, $start) {
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

function as_native_section_class($html) {
	if (preg_match('/<section\b[^>]*class="([^"]*)"/i', $html, $m)) {
		return trim(preg_replace('/\bas-divi-chunk\b/', '', $m[1]));
	}
	if (preg_match('/<div\b[^>]*class="([^"]*)"/i', $html, $m)) {
		return trim($m[1]);
	}
	return 'as-native-section';
}

function as_native_html_to_shortcodes($html) {
	$text_style = as_native_text_style();
	$out = '';
	foreach (as_native_split_top_level($html) as $chunk) {
		$chunk = trim($chunk);
		if ($chunk === '') {
			continue;
		}
		$out .= as_native_chunk_to_shortcode($chunk, $text_style);
	}
	return $out;
}

function as_native_chunk_to_shortcode($chunk, $text_style) {
	if (preg_match('/class="[^"]*\bas-hero\b/', $chunk)) {
		return as_native_wrap_text_section('as-hero', '0px|0px|0px|0px', '100%', '4_4', 'as-native-hero', $chunk, $text_style);
	}
	if (preg_match('/class="[^"]*\bas-banner\b/', $chunk)) {
		return as_native_wrap_text_section('as-banner', '4rem|0px|2.5rem|0px', '72rem', '4_4', 'as-native-banner', $chunk, $text_style);
	}
	if (preg_match('/class="[^"]*\bas-page as-prose\b/', $chunk)) {
		return as_native_wrap_text_section('as-native-prose', '0px|0px|3rem|0px', '44rem', '4_4', 'as-prose', $chunk, $text_style);
	}
	if (preg_match('/class="[^"]*\bas-section\b/', $chunk)) {
		return as_native_content_section($chunk, $text_style);
	}
	if (preg_match('/^\[gravityform/i', trim($chunk))) {
		return as_native_wrap_text_section('as-native-form', '0px|0px|3rem|0px', '44rem', '4_4', 'as-prose', $chunk, $text_style);
	}
	return as_native_wrap_text_section('as-native-prose', '0px|0px|2rem|0px', '44rem', '4_4', 'as-prose', $chunk, $text_style);
}

function as_native_wrap_text_section($section_class, $padding, $row_max, $col_type, $text_class, $html, $text_style) {
	return sprintf(
		'[et_pb_section fb_built="1" _builder_version="4.27.4" _module_preset="default" custom_padding="%1$s|false|false" background_color="RGBA(255,255,255,0)" module_class="%2$s"][et_pb_row _builder_version="4.27.4" _module_preset="default" custom_padding="0px|1.25rem|0px|1.25rem|false|false" max_width="%3$s"][et_pb_column type="%4$s" _builder_version="4.27.4" _module_preset="default"][et_pb_text _builder_version="4.27.4" _module_preset="default" module_class="%5$s" %6$s]%7$s[/et_pb_text][/et_pb_column][/et_pb_row][/et_pb_section]',
		esc_attr($padding),
		esc_attr($section_class),
		esc_attr($row_max),
		esc_attr($col_type),
		esc_attr($text_class),
		$text_style,
		$html
	);
}

function as_native_content_section($chunk, $text_style) {
	$section_class = as_native_section_class($chunk);
	$padding = '4.5rem|0px|4.5rem|0px';

	if (preg_match('/<div class="as-section__inner as-split(?: as-split--reverse)?">(.*)<\/div>\s*<\/section>/s', $chunk, $m)) {
		return as_native_split_section($section_class, $padding, $m[1], $text_style, strpos($chunk, 'as-split--reverse') !== false);
	}

	return as_native_wrap_text_section($section_class, $padding, '80rem', '4_4', 'as-prose', $chunk, $text_style);
}

function as_native_split_section($section_class, $padding, $inner, $text_style, $reverse) {
	$text_html = $inner;
	$image_html = '';
	if (preg_match('/<div class="as-split__media">(.*?)<\/div>/s', $inner, $im)) {
		$image_html = trim($im[1]);
		$text_html = preg_replace('/<div class="as-split__media">.*?<\/div>/s', '', $inner);
	}
	$text_html = trim($text_html);
	$image_module = as_native_image_module_from_html($image_html);

	$col1 = '[et_pb_column type="1_2" _builder_version="4.27.4" _module_preset="default"][et_pb_text _builder_version="4.27.4" _module_preset="default" module_class="as-prose" ' . $text_style . ']' . $text_html . '[/et_pb_text][/et_pb_column]';
	$col2 = '[et_pb_column type="1_2" _builder_version="4.27.4" _module_preset="default"]' . $image_module . '[/et_pb_column]';
	if ($reverse) {
		$cols = $col2 . $col1;
	} else {
		$cols = $col1 . $col2;
	}

	return '[et_pb_section fb_built="1" _builder_version="4.27.4" _module_preset="default" custom_padding="' . esc_attr($padding) . '|false|false" background_color="RGBA(255,255,255,0)" module_class="' . esc_attr($section_class) . '"][et_pb_row _builder_version="4.27.4" _module_preset="default" max_width="80rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false" column_structure="1_2,1_2" use_custom_gutter="on" gutter_width="2"]' . $cols . '[/et_pb_row][/et_pb_section]';
}

function as_native_image_module_from_html($html) {
	if (preg_match('/<img[^>]+src="([^"]+)"[^>]*alt="([^"]*)"/i', $html, $m)) {
		return '[et_pb_image _builder_version="4.27.4" _module_preset="default" module_class="as-native-image" src="' . esc_url($m[1]) . '" alt="' . esc_attr($m[2]) . '" title="" show_in_lightbox="off" align="center" /]';
	}
	if (preg_match('/<div class="as-img-placeholder"/', $html)) {
		return '[et_pb_text _builder_version="4.27.4" module_class="as-native-placeholder" ' . as_native_text_style() . ']' . $html . '[/et_pb_text]';
	}
	return '[et_pb_text _builder_version="4.27.4" module_class="as-native-image-fallback" ' . as_native_text_style() . ']' . $html . '[/et_pb_text]';
}

function as_native_news_blog_shortcode() {
	return '[et_pb_section fb_built="1" _builder_version="4.27.4" _module_preset="default" custom_padding="0px|0px|4.5rem|0px|false|false" background_color="rgba(255,255,255,0.45)" module_class="as-news-list-section"][et_pb_row _builder_version="4.27.4" _module_preset="default" max_width="44rem" custom_padding="2rem|0px|0px|0px|false|false"][et_pb_column type="4_4" _builder_version="4.27.4" _module_preset="default"][et_pb_blog fullwidth="on" posts_number="10" meta_date="F j, Y" show_thumbnail="off" show_content="off" show_more="off" show_author="off" show_date="on" show_categories="off" show_comments="off" show_excerpt="on" excerpt_length="32" show_pagination="on" use_overlay="off" _builder_version="4.27.4" _module_preset="default" module_class="as-news-blog" header_level="h2" header_font="Cormorant Garamond||||||||" header_font_size="1.5rem" header_text_color="#1E3D2C" body_font="Source Sans 3||||||||" body_text_color="#4A534C" meta_font="Source Sans 3||||||||" meta_text_color="#4A534C" meta_font_size="0.9rem" border_width_all="0px" custom_padding="0px|0px|0px|0px|false|false" /][/et_pb_column][/et_pb_row][/et_pb_section]';
}

function as_native_blocks_have_html($blocks) {
	foreach ($blocks as $block) {
		$name = $block['blockName'] ?? '';
		if (in_array($name, ['divi/blog', 'divi/image'], true)) {
			return true;
		}
		$val = $block['attrs']['content']['innerContent']['desktop']['value'] ?? '';
		if (is_string($val) && strpos($val, '<') !== false) {
			return true;
		}
		if (!empty($block['innerBlocks']) && as_native_blocks_have_html($block['innerBlocks'])) {
			return true;
		}
	}
	return false;
}

function as_native_convert_page($page, $backup_dir, $append_blog = false) {
	$slug = $page->post_name;
	$raw = $page->post_content;
	file_put_contents("{$backup_dir}/{$slug}.raw.txt", $raw);

	$html = as_native_fix_typos(as_native_extract_html($raw));
	$html = as_native_append_forms($html, $slug);
	if (strlen($html) < 20) {
		return "FAIL /{$slug}/ empty HTML\n";
	}
	file_put_contents("{$backup_dir}/{$slug}.html", $html);

	$shortcode = as_native_html_to_shortcodes($html);
	if ($append_blog) {
		$shortcode .= as_native_news_blog_shortcode();
	}
	if ($shortcode === '') {
		return "FAIL /{$slug}/ no shortcodes\n";
	}

	$converted = \ET\Builder\Packages\Conversion\Conversion::maybeConvertContent(
		$shortcode,
		true,
		(int) $page->ID,
		true
	);
	if (!$converted || strpos($converted, '<!-- wp:divi/') === false) {
		return "FAIL /{$slug}/ Divi 5 conversion\n";
	}
	if (!as_native_blocks_have_html(parse_blocks($converted))) {
		return "FAIL /{$slug}/ blocks missing HTML\n";
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
	preg_match_all('/wp:divi\/(\w+)/', $saved, $m);
	$counts = array_count_values($m[1]);
	$legacy = substr_count($saved, '[et_pb_');
	$codes = $counts['code'] ?? 0;

	return 'OK /' . ($slug === 'home' ? '' : $slug . '/') . " (#{$page->ID}) modules=" . wp_json_encode($counts) . " legacy={$legacy} codes={$codes}\n";
}
