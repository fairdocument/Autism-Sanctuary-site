<?php
/**
 * Fix updated-about / updated-people-page / updated-home:
 * - brand column widths (drop Divi 1440px + fixed heading widths)
 * - strip nested as-page wrappers
 * - remove empty leftover sections
 * - people: brand .as-person markup (was unstyled as-person-card)
 * - home: clean services/CTA HTML, Activity Barn typo
 */

if (!defined('ABSPATH')) {
	exit(1);
}

require_once __DIR__ . '/native-divi-lib.php';

$backup_dir = WP_CONTENT_DIR . '/as-updated-quality-backup-' . gmdate('Ymd-His');
wp_mkdir_p($backup_dir);

function as_uq_encode($html) {
	$html = (string) $html;
	// Divi stores HTML inside block comments; raw "<"/">" and "--" break the comment.
	$html = str_replace('\\', '\\\\', $html);
	$html = str_replace('--', '\\u002d\\u002d', $html);
	$html = strtr($html, [
		'<'  => '\\u003c',
		'>'  => '\\u003e',
		'"'  => '\\u0022',
		'&'  => '\\u0026',
		"'"  => '\\u0027',
	]);
	$html = str_replace(["\r\n", "\r", "\n"], '\\n', $html);
	$html = str_replace("\t", '\\t', $html);
	return $html;
}

function as_uq_remove_empty_banner_section($content) {
	$pos = 0;
	while (($i = strpos($content, '"value":"as-banner"', $pos)) !== false) {
		$head = substr($content, 0, $i);
		$start = strrpos($head, '<!-- wp:divi/section');
		if ($start === false) {
			$pos = $i + 1;
			continue;
		}
		$end = strpos($content, ' /-->', $i);
		if ($end === false) {
			break;
		}
		$end += strlen(' /-->');
		$chunk = substr($content, $start, $end - $start);
		if (strpos($chunk, '<!-- wp:divi/row') === false && substr_count($chunk, '<!-- wp:divi/section') === 1) {
			return [substr($content, 0, $start) . ltrim(substr($content, $end)), true];
		}
		$pos = $i + 1;
	}
	return [$content, false];
}

function as_uq_replace_text_value($content, $needle_substr, $new_html) {
	$encoded = as_uq_encode($new_html);
	$pattern = '/("innerContent":\{"desktop":\{"value":")(.*?)("\}\})/s';
	$count = 0;
	$content = preg_replace_callback($pattern, function ($m) use ($needle_substr, $encoded, &$count) {
		$decoded = as_native_decode_value($m[2]);
		if (strpos($decoded, $needle_substr) === false) {
			return $m[0];
		}
		$count++;
		return $m[1] . $encoded . $m[3];
	}, $content);
	return [$content, $count];
}

function as_uq_fix_layout_json($content) {
	// Brand column instead of Divi default 1440px.
	$content = str_replace('"maxWidth":"1440px"', '"maxWidth":"80rem"', $content);
	// Drop fixed left-biased module widths.
	$content = str_replace('"width":"700px"', '"width":"100%"', $content);
	$content = str_replace('"width":"500px"', '"width":"100%"', $content);
	return $content;
}

function as_uq_strip_outer_div($html) {
	$html = trim($html);
	if (preg_match('/^<div\b[^>]*class="[^"]*\bas-(?:page|prose)[^"]*"[^>]*>\s*(.*)\s*<\/div>\s*$/is', $html, $m)) {
		return trim($m[1]);
	}
	if (preg_match('/^<div>\s*(.*)\s*<\/div>\s*$/is', $html, $m)) {
		return trim($m[1]);
	}
	return $html;
}

function as_uq_people_html() {
	return <<<'HTML'
<div class="as-page as-prose">
<h2>Board of Directors</h2>
<div class="as-people">
  <div class="as-person">
    <div class="as-person__photo" aria-hidden="true">JB</div>
    <div>
      <h3>Jason Brewster</h3>
      <p class="as-person__role">President / Founder</p>
      <p>Parent of an autistic adult with high support needs and founder of Autism Sanctuary (2020). Entrepreneur and Director of Venture Programming at the University of Virginia’s Darden School of Business.</p>
    </div>
  </div>
  <div class="as-person">
    <div class="as-person__photo" aria-hidden="true">RK</div>
    <div>
      <h3>Robert Kreps</h3>
      <p class="as-person__role">Treasurer</p>
      <p>Parent of an autistic adult, retired engineer, and volunteer president of the Charlottesville Regional Autism Advocacy Group.</p>
    </div>
  </div>
  <div class="as-person">
    <div class="as-person__photo" aria-hidden="true">MO</div>
    <div>
      <h3>Matthew Osborne</h3>
      <p class="as-person__role">Secretary</p>
      <p>Director of Adult and Residential Services at the Faison Center. Psychologist and Licensed Board Certified Behavior Analyst.</p>
    </div>
  </div>
  <div class="as-person">
    <div class="as-person__photo" aria-hidden="true">RN</div>
    <div>
      <h3>Rose Neville</h3>
      <p class="as-person__role">Board Member</p>
      <p>Research Assistant Professor of Education and Director of the UVA Autism Research Core.</p>
    </div>
  </div>
</div>

<h2>Management</h2>
<div class="as-people">
  <div class="as-person">
    <div class="as-person__photo" aria-hidden="true">OB</div>
    <div>
      <h3>Olivia Bruno</h3>
      <p class="as-person__role">Executive Director</p>
      <p>Strategic leader focused on strengthening services and supporting the developmental disabilities community.</p>
    </div>
  </div>
  <div class="as-person">
    <div class="as-person__photo" aria-hidden="true">IK</div>
    <div>
      <h3>Isabelle (Izzy) Kueser</h3>
      <p class="as-person__role">Director of Adult Services</p>
      <p>Leads direct care teams and creates meaningful person-centered experiences.</p>
    </div>
  </div>
</div>
</div>
HTML;
}

function as_uq_home_services_html() {
	return <<<'HTML'
<p class="as-eyebrow">Our services</p>
<h2>Licensed support rooted in connection, growth, and belonging.</h2>
<p class="as-lede">Autism Sanctuary provides meaningful support through nature, community, and individualized services designed to help people thrive.</p>
<div class="as-actions"><a class="as-btn as-btn--primary" href="/programs/">Full service details</a></div>
<div class="as-service-grid">
  <div class="as-card">
    <h3>Group Day</h3>
    <p>On-site weekday support on the farm—animal care, gardens, trails, and skill-building in nature.</p>
  </div>
  <div class="as-card">
    <h3>Community Coaching</h3>
    <p>1:1 support in the community to build skills that open doors to everyday participation.</p>
  </div>
  <div class="as-card">
    <h3>Community Engagement</h3>
    <p>1:3 small-group support for meaningful outings and community life beyond the farm.</p>
  </div>
  <div class="as-card">
    <h3>Residential &amp; Home-Based</h3>
    <p>Personalized supports in people’s homes and community settings where authorized.</p>
  </div>
  <div class="as-card">
    <h3>Workplace Assistance</h3>
    <p>1:1 on-the-job support that helps people succeed in meaningful employment.</p>
  </div>
</div>
HTML;
}

function as_uq_home_cta_html() {
	return <<<'HTML'
<h2>Want to Learn More?</h2>
<p class="as-lede">Whether you are exploring services, volunteering, careers, or a gift—we are glad you reached out.</p>
<div class="as-actions"><a class="as-btn as-btn--primary" href="/contact/">Send an inquiry</a><a class="as-btn as-btn--ghost" href="/programs/">Explore services</a></div>
HTML;
}

function as_uq_strip_nested_section($html) {
	$html = trim($html);
	if (preg_match('/^<section\b[^>]*class="[^"]*\bas-section--forest[^"]*"[^>]*>\s*<div class="as-section__inner">\s*(.*)\s*<\/div>\s*<\/section>\s*$/is', $html, $m)) {
		return trim($m[1]);
	}
	return as_uq_strip_outer_div($html);
}

$targets = [
	416 => 'updated-about',
	419 => 'updated-people-page',
	409 => 'updated-home',
];

foreach ($targets as $id => $slug) {
	$post = get_post($id);
	if (!$post) {
		echo "MISS $id $slug\n";
		continue;
	}

	file_put_contents("$backup_dir/{$id}-{$slug}.html", $post->post_content);
	$content = $post->post_content;
	$notes = [];

	$content = as_uq_fix_layout_json($content);
	$notes[] = 'layout-widths';

	[$content, $removed] = as_uq_remove_empty_banner_section($content);
	if ($removed) {
		$notes[] = 'removed-empty-as-banner';
	}

	if ($id === 416) {
		foreach (['as-about-mvv-text', 'as-about-edgefield-body', 'Our Story'] as $needle) {
			$pattern = '/("innerContent":\{"desktop":\{"value":")(.*?)("\}\})/s';
			$content = preg_replace_callback($pattern, function ($m) use ($needle, &$notes) {
				$decoded = as_native_decode_value($m[2]);
				if (strpos($decoded, $needle) === false) {
					return $m[0];
				}
				$clean = as_native_fix_typos(as_uq_strip_outer_div($decoded));
				$notes[] = "stripped-wrapper:$needle";
				return $m[1] . as_uq_encode($clean) . $m[3];
			}, $content);
		}
	}

	if ($id === 419) {
		[$content, $n] = as_uq_replace_text_value($content, 'as-people-page', as_uq_people_html());
		$notes[] = "people-markup:$n";
	}

	if ($id === 409) {
		[$content, $n] = as_uq_replace_text_value($content, 'as-services', as_uq_home_services_html());
		$notes[] = "home-services:$n";

		[$content, $n] = as_uq_replace_text_value($content, 'as-section--forest', as_uq_home_cta_html());
		$notes[] = "home-cta:$n";

		// Fix Activity Bar → Activity Barn inside text modules.
		$content = str_replace('Activity Bar', 'Activity Barn', $content);
		$notes[] = 'activity-barn-typo';

		// Strip outer wrapper divs from approach / home / looking-ahead text modules.
		foreach (['Our approach', 'Our home', 'Looking ahead'] as $needle) {
			$pattern = '/("innerContent":\{"desktop":\{"value":")(.*?)("\}\})/s';
			$content = preg_replace_callback($pattern, function ($m) use ($needle, &$notes) {
				$decoded = as_native_decode_value($m[2]);
				if (strpos($decoded, $needle) === false) {
					return $m[0];
				}
				$clean = as_native_fix_typos(as_uq_strip_outer_div($decoded));
				$clean = str_replace('Activity Bar', 'Activity Barn', $clean);
				$notes[] = "stripped-home:$needle";
				return $m[1] . as_uq_encode($clean) . $m[3];
			}, $content);
		}
	}

	$content = as_native_fix_typos($content);

	wp_update_post([
		'ID'           => $id,
		'post_content' => wp_slash($content),
	]);
	update_post_meta($id, '_et_pb_use_builder', 'on');
	update_post_meta($id, '_et_builder_version', '5.0.0');

	echo "OK $id $slug :: " . implode(', ', $notes) . "\n";
}

echo "Backup: $backup_dir\n";
echo "DONE\n";
