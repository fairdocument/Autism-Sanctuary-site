<?php
/**
 * Rebuild broken Divi text modules → Code modules, preserving current copy.
 * Run: wp eval-file wordpress-migration/rebuild-from-current.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

/**
 * Only run when executed via `wp eval-file` (not require/include).
 */
function as_rebuild_should_run() {
	$script = $_SERVER['argv'][2] ?? '';
	return (php_sapi_name() === 'cli' && $script && strpos($script, 'rebuild-from-current.php') !== false);
}

if (!as_rebuild_should_run()) {
	return;
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

$backup_dir = WP_CONTENT_DIR . '/as-html-backup-' . gmdate('Ymd-His');
wp_mkdir_p($backup_dir);
echo "Backup dir: {$backup_dir}\n";

// Prefer HTML rescued in the prior extract pass when present.
$prior_html = [];
$dirs = glob(WP_CONTENT_DIR . '/as-html-backup-*', GLOB_ONLYDIR);
rsort($dirs);
foreach ($dirs as $dir) {
	foreach (glob($dir . '/*.html') as $file) {
		$base = basename($file, '.html');
		if (!isset($prior_html[$base]) && filesize($file) > 80) {
			$prior_html[$base] = file_get_contents($file);
		}
	}
	break; // newest backup only
}
if ($prior_html) {
	echo 'Prior HTML snapshots: ' . count($prior_html) . "\n";
}

$people_fallback = <<<'HTML'
<section class="as-banner"><div class="as-page"><p class="as-eyebrow">People</p><h1>The board and team who steward Autism Sanctuary.</h1><p class="as-lede">Governance and day-to-day leadership for our nonprofit care farm.</p></div></section>
<div class="as-page as-prose">
<p>Meet the volunteer board and staff leaders who guide Autism Sanctuary’s mission, programs, and daily operations.</p>
<h2>Board of Directors</h2>
<div class="as-people">
  <div class="as-person"><div class="as-person__photo" aria-hidden="true">JB</div><div><h3>Jason Brewster</h3><p class="as-person__role">President</p><p>Biography coming soon.</p></div></div>
  <div class="as-person"><div class="as-person__photo" aria-hidden="true">RK</div><div><h3>Robert Kreps</h3><p class="as-person__role">Treasurer</p><p>Biography coming soon.</p></div></div>
  <div class="as-person"><div class="as-person__photo" aria-hidden="true">MO</div><div><h3>Matthew Osborne</h3><p class="as-person__role">Secretary</p><p>Biography coming soon.</p></div></div>
  <div class="as-person"><div class="as-person__photo" aria-hidden="true">RN</div><div><h3>Rose Neville</h3><p class="as-person__role">Board member</p><p>Biography coming soon.</p></div></div>
</div>
<h2>Management</h2>
<div class="as-people">
  <div class="as-person"><div class="as-person__photo" aria-hidden="true">OB</div><div><h3>Olivia Bruno</h3><p class="as-person__role">Executive Director</p><p>Biography coming soon.</p></div></div>
  <div class="as-person"><div class="as-person__photo" aria-hidden="true">IK</div><div><h3>Isabelle (Izzy) Kueser</h3><p class="as-person__role">Director of Adult Services</p><p>Biography coming soon.</p></div></div>
</div>
</div>
HTML;

// Fallbacks only for pages wiped by a bad prior extract (migration copy).
$seed_fallbacks = as_seed_fallbacks();

$pages = get_posts([
	'post_type'      => 'page',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'orderby'        => 'menu_order title',
	'order'          => 'ASC',
]);

echo "=== Rebuild from current copy ===\n";

foreach ($pages as $page) {
	$slug = $page->post_name;
	$raw = $page->post_content;

	// Always backup raw post_content
	file_put_contents("{$backup_dir}/{$slug}.raw.txt", $raw);

	if ($slug === 'news') {
		as_fix_news_page($page, $backup_dir);
		continue;
	}

	$html = '';
	$source = '';

	// Prefer rescued HTML snapshots, then extract from current Divi markup.
	if ($slug === 'people' && (strpos($raw, 'Board and team test') !== false || strpos($raw, 'Slash test') !== false || strlen(as_extract_html_from_divi($raw)) < 500)) {
		$html = $prior_html['people'] ?? $people_fallback;
		$source = isset($prior_html['people']) ? 'prior-backup' : 'people-fallback';
	} elseif (!empty($prior_html[$slug]) && strlen($prior_html[$slug]) > 80) {
		$html = $prior_html[$slug];
		$source = 'prior-backup';
	} else {
		$html = as_extract_html_from_divi($raw);
		$source = 'extracted';
	}

	// Damaged by prior bad run
	if (strlen($html) < 80 && isset($seed_fallbacks[$slug])) {
		$html = $seed_fallbacks[$slug];
		$source = 'seed-fallback';
	}

	if (strlen($html) < 40) {
		echo "FAIL /{$slug}/ no HTML (len=" . strlen($html) . ")\n";
		continue;
	}

	file_put_contents("{$backup_dir}/{$slug}.html", $html);

	$html = as_fix_media_urls($html);
	$shortcode = as_html_to_code_sections($html);
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

	// Conversion already emits valid \u003c JSON escapes — do not re-encode.
	$ok = as_verify_blocks_html($converted);
	if (!$ok) {
		$converted = as_hex_encode_inner_content($converted);
		$ok = as_verify_blocks_html($converted);
	}

	if (!$ok) {
		echo "FAIL /{$slug}/ parse_blocks empty — not saving\n";
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

	echo "OK /{$slug}/ (#{$page->ID}) via {$source} html=" . strlen($html) . " codes=" . substr_count($converted, 'wp:divi/code') . "\n";
}

as_fix_posts_encoding($backup_dir);

echo "=== Done ===\n";

function as_extract_html_from_divi($content) {
	$parts = [];
	if (!preg_match_all('/<!-- wp:divi\/(text|code) (.*?) \/?-->/s', $content, $all, PREG_SET_ORDER)) {
		return '';
	}
	foreach ($all as $m) {
		$attrs = $m[2];
		if (!preg_match('/"innerContent":\{"desktop":\{"value":"(.*)"\}\}/s', $attrs, $vm)) {
			continue;
		}
		$html = $vm[1];
		// Newlines were collapsed to literal "n" between tags by a prior bad encode.
		$html = preg_replace('/(?<=>)n+(?=<)/', "\n", $html);
		$html = str_replace(['\\n', '\\"', '\\/'], ["\n", '"', '/'], $html);
		if (strpos($html, 'u003c') !== false) {
			$html = strtr($html, [
				'u003c' => '<', 'u003e' => '>', 'u003C' => '<', 'u003E' => '>',
				'u0022' => '"', 'u0026' => '&', 'u003d' => '=', 'u0027' => "'",
			]);
		}
		$parts[] = $html;
	}
	return trim(implode("\n", $parts));
}

function as_fix_media_urls($html) {
	return preg_replace('#https:/(?!/)#', 'https://', $html);
}

function as_html_to_code_sections($html) {
	$html = trim($html);
	$chunks = as_split_page_chunks($html);
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

function as_hex_encode_inner_content($content) {
	return preg_replace_callback(
		'/<!-- wp:divi\/(text|code) (.*?) \/?-->/s',
		static function ($m) {
			$attrs = $m[2];
			$self = substr($m[0], -4) === '/-->' ? ' /-->' : ' -->';
			if (!preg_match('/"innerContent":\{"desktop":\{"value":"(.*)"\}\}/s', $attrs, $vm)) {
				return $m[0];
			}
			$html = $vm[1];
			$html = preg_replace('/(?<=>)n+(?=<)/', "\n", $html);
			$html = str_replace(['\\n', '\\"', '\\/'], ["\n", '"', '/'], $html);
			if (strpos($html, 'u003c') !== false) {
				$html = strtr($html, [
					'u003c' => '<', 'u003e' => '>', 'u0022' => '"', 'u0026' => '&', 'u003d' => '=',
				]);
			}
			$enc = substr(json_encode($html, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES), 1, -1);
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

function as_verify_blocks_html($content) {
	return as_blocks_have_html(parse_blocks($content));
}

function as_blocks_have_html($blocks) {
	foreach ($blocks as $b) {
		$val = $b['attrs']['content']['innerContent']['desktop']['value'] ?? '';
		if (is_string($val) && strpos($val, '<') !== false) {
			return true;
		}
		if (!empty($b['innerBlocks']) && as_blocks_have_html($b['innerBlocks'])) {
			return true;
		}
	}
	return false;
}

function as_fix_news_page($page, $backup_dir) {
	$c = $page->post_content;
	file_put_contents("{$backup_dir}/news.raw.txt", $c);
	$html = as_extract_html_from_divi($c);
	file_put_contents("{$backup_dir}/news.html", $html);

	$blog = '';
	if (preg_match('/<!-- wp:divi\/blog .*?\/-->/s', $c, $m)) {
		$blog = $m[0];
	}

	if ($html === '') {
		echo "WARN /news/ no banner HTML\n";
		return;
	}

	$shortcode = as_html_to_code_sections($html);
	$converted = \ET\Builder\Packages\Conversion\Conversion::maybeConvertContent($shortcode, true, (int) $page->ID, true);
	if (!as_verify_blocks_html($converted)) {
		$converted = as_hex_encode_inner_content($converted);
	}
	if ($blog) {
		$converted .= "\n" . $blog;
	}
	if (!as_verify_blocks_html($converted)) {
		echo "FAIL /news/ verify\n";
		return;
	}
	wp_update_post(['ID' => $page->ID, 'post_content' => wp_slash($converted)]);
	echo "OK /news/ banner rebuilt + blog kept\n";
}

function as_fix_posts_encoding($backup_dir) {
	$q = new WP_Query([
		'post_type'      => ['post', 'et_body_layout'],
		'post_status'    => ['publish', 'private'],
		'posts_per_page' => -1,
	]);
	foreach ($q->posts as $post) {
		$c = $post->post_content;
		if (strpos($c, 'innerContent') === false) {
			continue;
		}
		file_put_contents("{$backup_dir}/{$post->post_type}-{$post->ID}.raw.txt", $c);
		$html = as_extract_html_from_divi($c);
		if ($html === '' || strlen($html) < 20) {
			$fixed = as_hex_encode_inner_content($c);
			if ($fixed !== $c && as_verify_blocks_html($fixed)) {
				wp_update_post(['ID' => $post->ID, 'post_content' => wp_slash($fixed)]);
				echo "OK {$post->post_type} #{$post->ID} hex-only\n";
			}
			continue;
		}
		file_put_contents("{$backup_dir}/{$post->post_type}-{$post->ID}.html", $html);
		if ($post->post_type === 'et_body_layout') {
			$fixed = as_hex_encode_inner_content($c);
			if (as_verify_blocks_html($fixed)) {
				wp_update_post(['ID' => $post->ID, 'post_content' => wp_slash($fixed)]);
				echo "OK layout #{$post->ID} hex\n";
			} else {
				echo "WARN layout #{$post->ID} left as-is\n";
			}
			continue;
		}
		$sc = '[et_pb_section fb_built="1" _builder_version="4.27.4" module_class="as-prose as-divi-chunk" custom_padding="0px|0px|0px|0px|false|false" background_color="RGBA(255,255,255,0)"][et_pb_row _builder_version="4.27.4" custom_padding="0px|0px|0px|0px|false|false" width="100%" max_width="100%"][et_pb_column type="4_4" _builder_version="4.27.4"][et_pb_code _builder_version="4.27.4"]' . $html . '[/et_pb_code][/et_pb_column][/et_pb_row][/et_pb_section]';
		$converted = \ET\Builder\Packages\Conversion\Conversion::maybeConvertContent($sc, true, (int) $post->ID, true);
		if (!as_verify_blocks_html($converted)) {
			echo "WARN post #{$post->ID} skip\n";
			continue;
		}
		wp_update_post(['ID' => $post->ID, 'post_content' => wp_slash($converted)]);
		echo "OK post #{$post->ID} {$post->post_name}\n";
	}
}

function as_seed_fallbacks() {
	$inquiry = '';
	if (class_exists('GFAPI')) {
		$forms = GFAPI::get_forms();
		foreach ($forms as $f) {
			if (stripos($f['title'], 'inqui') !== false) {
				$inquiry = '[gravityform id="' . (int) $f['id'] . '" title="false" description="false" ajax="true"]';
				break;
			}
		}
	}
	$house = '';
	$q = new WP_Query(['post_type' => 'attachment', 'posts_per_page' => 50, 'fields' => 'ids']);
	foreach ($q->posts as $id) {
		if (basename(get_attached_file($id)) === 'edgefield-house.jpg') {
			$house = wp_get_attachment_url($id);
			break;
		}
	}

	$banner = function ($eyebrow, $title, $lede = '') {
		$html = '<section class="as-banner"><div class="as-page">';
		$html .= '<p class="as-eyebrow">' . esc_html($eyebrow) . '</p>';
		$html .= '<h1>' . esc_html($title) . '</h1>';
		if ($lede) {
			$html .= '<p class="as-lede">' . esc_html($lede) . '</p>';
		}
		$html .= '</div></section>';
		return $html;
	};
	$prose = function ($body) {
		return '<div class="as-page as-prose">' . $body . '</div>';
	};

	$contact_body = '<div class="as-contact-grid"><div class="as-aside">
<p><strong>Visit</strong><br>2860 Pea Ridge Road<br>Charlottesville, VA 22901</p>
<p><strong>Call</strong><br><a href="tel:+14342072118">(434) 207-2118</a></p>
<p><strong>Email</strong><br><a href="mailto:info@autismsanctuary.org">info@autismsanctuary.org</a></p>
<p><a href="https://www.instagram.com/autismsanctuary">Instagram</a> · <a href="https://www.facebook.com/autismsanctuary">Facebook</a></p>
' . ($house ? '<figure style="margin-top:1.5rem"><img src="' . esc_url($house) . '" alt="Edgefield farmhouse"></figure>' : '') . '
</div><div><h2>Send an inquiry</h2>' . $inquiry . '</div></div>';

	return [
		'contact' => $banner('Contact', 'We are glad you reached out.', 'Ask about programs, volunteering, jobs, donating, or general information.') . $prose($contact_body),
		'privacy' => $banner('Legal', 'Privacy', 'How we handle information you share with us.') . $prose('<p>Autism Sanctuary collects contact information you submit through inquiry and donation forms to respond to your request and process gifts. We do not sell personal information. When online payments are enabled, payment details are processed by Stripe and are not stored on this site.</p><p>For privacy questions, email <a href="mailto:info@autismsanctuary.org">info@autismsanctuary.org</a>.</p>'),
		'terms'   => $banner('Legal', 'Terms', 'Informational disclaimer for this website.') . $prose('<p>Content on this website is for general information about Autism Sanctuary’s programs and property. It is not medical, legal, or eligibility advice. Service authorization depends on individualized planning and payer rules.</p><p>Questions: <a href="mailto:info@autismsanctuary.org">info@autismsanctuary.org</a>.</p>'),
		'thanks'  => $banner('Thank you', 'Your generosity keeps farm days joyful.', 'We appreciate your support of Autism Sanctuary.') . $prose('<p>If you submitted a gift inquiry, we will follow up shortly with next steps. Questions? Email <a href="mailto:info@autismsanctuary.org">info@autismsanctuary.org</a>.</p><div class="as-actions"><a class="as-btn as-btn--primary" href="/">Return home</a><a class="as-btn as-btn--ghost" href="/our-farm/">Explore the farm</a></div>'),
	];
}
