<?php
/**
 * Align WP page markup with EmDash patterns (checklist colons, people initials, link slashes).
 * Run: wp eval-file wordpress-migration/polish-design.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

echo "=== Design polish ===\n";

// Fix home checklist to match EmDash (colon after bold labels)
$home = get_page_by_path('home');
if ($home) {
	$content = $home->post_content;
	$content = str_replace(
		'<li><strong>High support needs welcome</strong> most people we serve rely on consistent, skilled staffing.</li>',
		'<li><strong>High support needs welcome</strong>: most people we serve rely on consistent, skilled staffing.</li>',
		$content
	);
	$content = str_replace(
		'<li><strong>Evidence + practice here</strong> research-backed methods where they fit, and promising practices refined on this farm.</li>',
		'<li><strong>Evidence + practice here</strong>: research-backed methods where they fit, and promising practices refined on this farm.</li>',
		$content
	);
	$content = str_replace(
		'<li><strong>Local first</strong> Crozet, White Hall, and the wider Albemarle community help make the farm feel like more than a program.</li>',
		'<li><strong>Local first</strong>: Crozet, White Hall, and the wider Albemarle community help make the farm feel like more than a program.</li>',
		$content
	);
	// Normalize double slashes from prior migration
	$content = str_replace(['/?', '//'], ['/?', '/'], $content);
	$content = preg_replace('#(?<!:)/(/)+#', '/', $content);
	wp_update_post(['ID' => $home->ID, 'post_content' => $content]);
	echo "Home checklist updated\n";
}

// People page — EmDash person-item with initials block
$people = get_page_by_path('people');
if ($people) {
	$banner = '';
	if (preg_match('/(<section class="as-banner">.*?<\/section>)/s', $people->post_content, $m)) {
		$banner = $m[1];
	} else {
		$banner = '<section class="as-banner"><div class="as-page"><p class="as-eyebrow">People</p><h1>The board and team who steward Autism Sanctuary.</h1><p class="as-lede">Governance and day-to-day leadership for our nonprofit care farm.</p></div></section>';
	}

	$body = '<div class="as-page as-prose">
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
</div>';

	wp_update_post(['ID' => $people->ID, 'post_content' => $banner . "\n" . $body]);
	echo "People page updated\n";
}

// Fix double-slash links on all pages
$q = new WP_Query([
	'post_type'      => 'page',
	'post_status'    => 'publish',
	'posts_per_page' => 50,
	'fields'         => 'ids',
]);
foreach ($q->posts as $id) {
	$c = get_post_field('post_content', $id);
	$fixed = preg_replace('#(href=["\'])(/[^"\']*)//+#', '$1$2/', $c);
	$fixed = str_replace(['/resources//', '/admissions//', '/programs//', '/contact//', '/our-farm//', '/donate//'], ['/resources/', '/admissions/', '/programs/', '/contact/', '/our-farm/', '/donate/'], $fixed);
	if ($fixed !== $c) {
		wp_update_post(['ID' => $id, 'post_content' => $fixed]);
		echo "Fixed links on page #{$id}\n";
	}
}

// Refresh custom CSS option copies
$css_path = __DIR__ . '/custom.css';
if (file_exists($css_path)) {
	$css = file_get_contents($css_path);
	wp_update_custom_css_post($css);
	$et = get_option('et_divi', []);
	if (!is_array($et)) {
		$et = [];
	}
	$et['divi_custom_css'] = $css;
	update_option('et_divi', $et);
	echo "Custom CSS refreshed (" . strlen($css) . " bytes)\n";
}

echo "=== Done ===\n";
