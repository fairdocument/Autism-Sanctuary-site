<?php
/**
 * Restore marketing pages as plain HTML (Divi builder off) from HTML backups.
 * Fixes garbled u003c output and literal "n" between tags.
 *
 * Run: wp eval-file wordpress-migration/restore-html-pages.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

$admins = get_users(['role' => 'administrator', 'number' => 1]);
if ($admins) {
	wp_set_current_user((int) $admins[0]->ID);
}

$dirs = glob(WP_CONTENT_DIR . '/as-html-backup-*', GLOB_ONLYDIR);
rsort($dirs);
$backup = $dirs[0] ?? '';
if (!$backup) {
	echo "No HTML backup directory found.\n";
	return;
}
echo "Using backup: {$backup}\n";

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

$slugs = [
	'home', 'about', 'programs', 'our-farm', 'admissions', 'people',
	'resources', 'careers', 'fellowship', 'donate', 'thanks', 'contact',
	'privacy', 'terms',
];

echo "=== Restore plain HTML pages ===\n";

foreach ($slugs as $slug) {
	$page = ($slug === 'thanks')
		? get_page_by_path('donate/thanks')
		: get_page_by_path($slug);
	if (!$page) {
		echo "Missing /{$slug}/\n";
		continue;
	}

	$file = "{$backup}/{$slug}.html";
	$html = '';
	if (is_readable($file) && filesize($file) > 80) {
		$html = file_get_contents($file);
	} elseif ($slug === 'people') {
		$html = $people_fallback;
	} else {
		// Last resort: pull from current Divi markup
		$html = as_extract_html_loose($page->post_content);
	}

	$html = as_clean_restored_html($html);
	if (strlen($html) < 40) {
		echo "FAIL /{$slug}/ empty HTML\n";
		continue;
	}

	wp_update_post([
		'ID'           => $page->ID,
		'post_content' => $html,
	]);
	update_post_meta($page->ID, '_et_pb_use_builder', 'off');
	update_post_meta($page->ID, '_et_pb_page_layout', 'et_no_sidebar');
	update_post_meta($page->ID, '_et_pb_show_title', 'off');
	delete_post_meta($page->ID, '_et_builder_version');

	echo "OK /{$slug}/ (#{$page->ID}) " . strlen($html) . " bytes\n";
}

// News: keep Divi Blog module — only fix banner as plain HTML above the blog if needed.
$news = get_page_by_path('news');
if ($news) {
	$banner_file = "{$backup}/news.html";
	$banner = is_readable($banner_file) ? as_clean_restored_html(file_get_contents($banner_file)) : '';
	$c = $news->post_content;
	$blog = '';
	if (preg_match('/<!-- wp:divi\/blog .*?\/-->/s', $c, $m)) {
		$blog = $m[0];
	} elseif (preg_match('/\[et_pb_blog.*?\[\/et_pb_blog\]/s', $c, $m)) {
		$blog = $m[0];
	}
	if ($banner && $blog) {
		// News needs Divi for Blog module — wrap banner in one code section with wp_slash
		if (class_exists('\\ET\\Builder\\Packages\\Conversion\\Conversion')) {
			\ET\Builder\Packages\Conversion\Conversion::initialize_shortcode_framework();
			$sc = '[et_pb_section fb_built="1" _builder_version="4.27.4" module_class="as-banner as-divi-chunk" custom_padding="0px|0px|0px|0px|false|false" background_color="RGBA(255,255,255,0)"][et_pb_row _builder_version="4.27.4" custom_padding="0px|0px|0px|0px|false|false" width="100%" max_width="100%"][et_pb_column type="4_4" _builder_version="4.27.4"][et_pb_code _builder_version="4.27.4"]' . $banner . '[/et_pb_code][/et_pb_column][/et_pb_row][/et_pb_section]';
			$converted = \ET\Builder\Packages\Conversion\Conversion::maybeConvertContent($sc, true, (int) $news->ID, true);
			wp_update_post([
				'ID'           => $news->ID,
				'post_content' => wp_slash($converted . "\n" . $blog),
			]);
			update_post_meta($news->ID, '_et_pb_use_builder', 'on');
			echo "OK /news/ banner+blog\n";
		}
	}
}

if (function_exists('wp_cache_flush')) {
	wp_cache_flush();
}

echo "=== Done ===\n";

function as_clean_restored_html($html) {
	$html = (string) $html;
	// Literal "n" placeholders between tags from bad newline encoding
	$html = preg_replace('/(?<=>)n+(?=<)/', "\n", $html);
	// Bare unicode escapes
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

function as_extract_html_loose($content) {
	$parts = [];
	if (preg_match_all('/<!-- wp:divi\/(text|code) (.*?) \/?-->/s', $content, $all, PREG_SET_ORDER)) {
		foreach ($all as $m) {
			if (preg_match('/"innerContent":\{"desktop":\{"value":"(.*)"\}\}/s', $m[2], $vm)) {
				$parts[] = $vm[1];
			}
		}
	}
	return trim(implode("\n", $parts));
}
