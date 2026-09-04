<?php
/**
 * Rebuild /programs/ layout: About-style header + split service rows (like About/Our farm).
 *
 * Run: wp eval-file wordpress-migration/rebuild-programs-layout.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

require_once __DIR__ . '/native-divi-lib.php';

if (!class_exists('\\ET\\Builder\\Packages\\Conversion\\Conversion')) {
	echo "Divi 5 Conversion API not available.\n";
	return;
}

echo "=== Rebuild Programs layout ===\n";

$admins = get_users(['role' => 'administrator', 'number' => 1]);
if ($admins) {
	wp_set_current_user((int) $admins[0]->ID);
}
\ET\Builder\Packages\Conversion\Conversion::initialize_shortcode_framework();

$page = get_page_by_path('programs');
if (!$page) {
	echo "Programs page missing\n";
	return;
}

$backup = WP_CONTENT_DIR . '/as-programs-layout-backup-' . gmdate('Ymd-His') . '.txt';
file_put_contents($backup, $page->post_content);
echo "Backup: {$backup}\n";

$text_style = as_native_text_style();

function as_prog_media($filename) {
	static $map = null;
	if ($map === null) {
		$map = [];
		$q = new WP_Query([
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		]);
		foreach ($q->posts as $id) {
			$file = basename(get_attached_file($id));
			$map[strtolower($file)] = wp_get_attachment_url($id);
		}
	}
	$key = strtolower($filename);
	$url = $map[$key] ?? '';
	if (!$url) {
		foreach ($map as $name => $u) {
			if (stripos($name, pathinfo($filename, PATHINFO_FILENAME)) !== false) {
				$url = $u;
				break;
			}
		}
	}
	$path = $url ? wp_parse_url($url, PHP_URL_PATH) : '';
	return $path ?: $url;
}

$media = [
	as_prog_media('Pavilion-edited-scaled.jpg'),
	as_prog_media('farm-animals.jpg'),
	as_prog_media('DBBD5437-8C33-454A-ACF4-94B39019DC9F.JPG'),
	as_prog_media('72314FA3-D916-46D1-A3EC-AF43E063A151.JPG'),
	as_prog_media('edgefield.jpg'),
];

$gf = '[gravityform id="1" title="false" description="false" ajax="true"]';

$banner = as_prog_about_style_banner();

$intro = sprintf(
	'[et_pb_section fb_built="1" _builder_version="4.27.4" _module_preset="default" custom_padding="3rem|0px|2rem|0px|false|false" background_color="RGBA(255,255,255,0)" module_class="as-programs-intro"][et_pb_row _builder_version="4.27.4" _module_preset="default" max_width="44rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false"][et_pb_column type="4_4" _builder_version="4.27.4" _module_preset="default"][et_pb_text _builder_version="4.27.4" _module_preset="default" module_class="as-prose as-programs-intro-copy" %1$s]%2$s[/et_pb_text][/et_pb_column][/et_pb_row][/et_pb_section]',
	$text_style,
	<<<'HTML'
<h2>Licensed service lines</h2>
<p>Autism Sanctuary is a Virginia DBHDS-licensed provider serving adults with developmental disabilities. Licensed services show up both alongside our farming operations as well as out in the community and in people’s homes, creating a more integrated support structure tailored to each individual’s needs.</p>
<p>A range of support ratios is provided based on individual support needs.</p>
HTML
);

$services = [
	[
		'title' => 'Group Day',
		'alt'   => 'Group Day on the farm pavilion',
		'src'   => $media[0],
		'body'  => <<<'HTML'
<h3>Group Day</h3>
<p>On-site service running Monday through Friday, 9 AM–3 PM on an 85-acre farm. Individuals build confidence and develop skills through hands-on agricultural experiences, including animal care and gardening in the high tunnel, as well as through nature-based activities and community outings.</p>
<ul>
<li>Small-group and side-by-side modeling with trained DSPs</li>
<li>Trails, gardens, animals, and hands-on farm activities</li>
</ul>
HTML
	],
	[
		'title' => 'Community Coaching (1:1)',
		'alt'   => 'Community Coaching',
		'src'   => $media[1],
		'body'  => <<<'HTML'
<h3>Community Coaching (1:1)</h3>
<p>Off-site, individualized support that helps people build the social and situational skills needed to participate more independently in community settings. Community Coaching does not take place on the farm.</p>
<ul>
<li>1:1 coaching tailored to each person’s goals</li>
<li>Practice in real community environments at each person’s pace</li>
</ul>
HTML
	],
	[
		'title' => 'Community Engagement (1:3)',
		'alt'   => 'Community Engagement',
		'src'   => $media[2],
		'body'  => <<<'HTML'
<h3>Community Engagement (1:3)</h3>
<p>Off-site small-group support for meaningful community participation—libraries, local businesses, other farms, nature trails, and shared activities. Community Engagement does not take place on the farm.</p>
<ul>
<li>1:3 support ratio</li>
<li>Guided outings that build confidence and connection</li>
</ul>
HTML
	],
	[
		'title' => 'Residential & Home-Based Supports',
		'alt'   => 'Residential and Home Based Supports',
		'src'   => $media[3],
		'body'  => <<<'HTML'
<h3>Residential &amp; Home-Based Supports</h3>
<p>Lifespan support for adults who benefit from stable, individualized residential arrangements where authorized—including Sponsored Residential, In-Home Support, and Supported Living. Person-centered planning aligned with each individual’s goals stands true across these services.</p>
HTML
	],
	[
		'title' => 'Workplace Assistance',
		'alt'   => 'Workplace Assistance',
		'src'   => $media[4],
		'body'  => <<<'HTML'
<h3>Workplace Assistance</h3>
<p>Offers vocational support that helps individuals with higher support needs maintain meaningful employment. Staff provide 1:1 on the job support and workplace navigation to support long-term success.</p>
<ul>
<li>1:1 support</li>
<li>Employer partnership</li>
<li>Focus on long-term success</li>
</ul>
HTML
	],
];

$service_sc = '';
foreach ($services as $i => $svc) {
	$service_sc .= as_prog_split_service($svc, $text_style, $i % 2 === 1);
}

$interest = sprintf(
	'[et_pb_section fb_built="1" _builder_version="4.27.4" _module_preset="default" custom_padding="3rem|0px|2rem|0px|false|false" background_color="RGBA(255,255,255,0)" module_class="as-programs-interest"][et_pb_row _builder_version="4.27.4" _module_preset="default" max_width="44rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false"][et_pb_column type="4_4" _builder_version="4.27.4" _module_preset="default"][et_pb_text _builder_version="4.27.4" _module_preset="default" module_class="as-prose as-programs-interest-copy" %1$s]%2$s[/et_pb_text][/et_pb_column][/et_pb_row][/et_pb_section]',
	$text_style,
	<<<HTML
<h2 id="interest">Explore a program fit</h2>
<p>Our admissions team works closely with individuals, families, guardians, and case managers to understand each person’s goals, strengths, and needs and identify the supports that are the best fit.</p>
<p>Use the inquiry form below to start the conversation. For the full multi-step intake still in production, you may also use <a href="https://www.autismsanctuary.org/intake-form/">the current intake form</a>.</p>
{$gf}
<p><a href="/resources/">Waiver resources</a> · <a href="/contact/?intent=program-fit">Contact us</a></p>
HTML
);

$auth = sprintf(
	'[et_pb_section fb_built="1" _builder_version="4.27.4" _module_preset="default" custom_padding="0px|0px|4.5rem|0px|false|false" background_color="RGBA(255,255,255,0)" module_class="as-programs-auth-callout"][et_pb_row _builder_version="4.27.4" _module_preset="default" max_width="44rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false"][et_pb_column type="4_4" _builder_version="4.27.4" _module_preset="default"][et_pb_text _builder_version="4.27.4" _module_preset="default" module_class="as-prose as-programs-auth-copy" %1$s]%2$s[/et_pb_text][/et_pb_column][/et_pb_row][/et_pb_section]',
	$text_style,
	<<<'HTML'
<div class="as-callout as-callout--auth">
<p><strong>Authorization and eligibility:</strong> Service availability, funding, and authorization vary by person and payer. Many people access services through Virginia’s Medicaid waiver programs; private-pay arrangements are also available. See <a href="/resources/">Resources</a> or the interest form above.</p>
</div>
HTML
);

$shortcode = $intro . $service_sc . $interest . $auth;
$converted = \ET\Builder\Packages\Conversion\Conversion::maybeConvertContent(
	$shortcode,
	true,
	(int) $page->ID,
	true
);

if (!$converted || strpos($converted, '<!-- wp:divi/') === false) {
	echo "FAIL body conversion\n";
	return;
}

$content = trim($banner) . "\n\n" . ltrim($converted);

if (strlen($content) < 8000 || strpos($content, 'Group Day') === false) {
	echo "FAIL abort — content too short or missing services\n";
	return;
}

wp_update_post([
	'ID'           => $page->ID,
	'post_content' => wp_slash($content),
	'post_excerpt' => 'Licensed supports on the farm, in the community, and in people’s homes.',
]);
update_post_meta($page->ID, '_et_pb_use_builder', 'on');
update_post_meta($page->ID, '_et_pb_page_layout', 'et_no_sidebar');
update_post_meta($page->ID, '_et_pb_show_title', 'off');
update_post_meta($page->ID, '_et_builder_version', '5.0.0');

$cache = WP_CONTENT_DIR . '/et-cache/' . $page->ID;
if (is_dir($cache)) {
	$it = new RecursiveDirectoryIterator($cache, FilesystemIterator::SKIP_DOTS);
	$files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
	foreach ($files as $file) {
		$file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
	}
	@rmdir($cache);
}
wp_cache_flush();

$saved = get_post_field('post_content', $page->ID);
preg_match_all('/wp:divi\/(\w+)/', $saved, $m);
$counts = array_count_values($m[1]);
echo 'OK modules=' . wp_json_encode($counts) . "\n";
echo 'heading=' . (($counts['heading'] ?? 0) > 0 ? 'yes' : 'no') . "\n";
echo 'as-banner=' . (strpos($saved, 'as-programs-banner') !== false || strpos($saved, 'as-banner as-programs') !== false ? 'old' : 'none') . "\n";
echo 'len=' . strlen($saved) . "\n";
echo "=== Done ===\n";

/**
 * Exact About banner structure with Programs copy.
 */
function as_prog_about_style_banner() {
	$eyebrow_json = 'Programs & licensed services\\n';
	$heading_json = 'Licensed support rooted in connection, growth, and belonging.';
	$lede_json = 'Autism Sanctuary is a Virginia DBHDS-licensed provider serving adults with developmental disabilities—on the farm, in the community, and in people’s homes.\\n\\n';

	return <<<BLOCKS
<!-- wp:divi/placeholder --><!-- wp:divi/section {"module":{"decoration":{"background":{"desktop":{"value":{"color":"\$variable({\u0022type\u0022:\u0022color\u0022,\u0022value\u0022:{\u0022name\u0022:\u0022gcid-22sbvlqwkx\u0022,\u0022settings\u0022:{}}})$"}}},"spacing":{"desktop":{"value":{"padding":{"top":"80px","syncVertical":"off","syncHorizontal":"off","bottom":"40px"}}},"tablet":{"value":{"padding":{"syncVertical":"off","syncHorizontal":"on","left":"15px","right":"15px"}}}}}},"builderVersion":"5.11.1"} -->
<!-- wp:divi/row {"module":{"advanced":{"columnStructure":{"desktop":{"value":"4_4"}},"flexColumnStructure":{"desktop":{"value":"equal-columns_1"}}},"decoration":{"layout":{"desktop":{"value":{"flexWrap":"nowrap"}}},"sizing":{"desktop":{"value":{"width":"100%","maxWidth":"80rem"}}}}},"builderVersion":"5.11.1"} -->
<!-- wp:divi/column {"module":{"advanced":{"type":{"desktop":{"value":"4_4"}}},"decoration":{"sizing":{"desktop":{"value":{"flexType":"24_24"}}},"layout":{"desktop":{"value":{"rowGap":"15px"}}}}},"builderVersion":"5.11.1"} -->
<!-- wp:divi/text {"content":{"innerContent":{"desktop":{"value":"{$eyebrow_json}"}},"decoration":{"bodyFont":{"body":{"font":{"desktop":{"value":{"size":"16px","color":"#ffffff"}}}}}}},"builderVersion":"5.11.1"} /-->

<!-- wp:divi/heading {"module":{"decoration":{"sizing":{"desktop":{"value":{"width":"100%"}},"phone":{"value":{"width":"100%"}}}}},"title":{"innerContent":{"desktop":{"value":"{$heading_json}"}},"decoration":{"font":{"font":{"desktop":{"value":{"color":"#ffffff","size":"40px","weight":"700","weightFineTune":"","variationSettings":{"WGHT":""}}}}}}},"builderVersion":"5.11.1"} /-->

<!-- wp:divi/text {"content":{"innerContent":{"desktop":{"value":"{$lede_json}"}},"decoration":{"bodyFont":{"body":{"font":{"desktop":{"value":{"size":"16px","color":"#ffffff"}}}}}}},"builderVersion":"5.11.1"} /-->
<!-- /wp:divi/column -->
<!-- /wp:divi/row -->
<!-- /wp:divi/section -->
BLOCKS;
}

function as_prog_split_service(array $svc, $text_style, $reverse) {
	$src = $svc['src'];
	$image = $src
		? '[et_pb_image _builder_version="4.27.4" _module_preset="default" module_class="as-programs-service-photo" src="' . esc_url($src) . '" alt="' . esc_attr($svc['alt']) . '" title="' . esc_attr($svc['title']) . '" show_in_lightbox="off" align="center" force_fullwidth="on" /]'
		: '[et_pb_text _builder_version="4.27.4" module_class="as-native-placeholder" ' . $text_style . ']<div class="as-img-placeholder" role="img" aria-label="' . esc_attr($svc['alt']) . '"><span>' . esc_html($svc['alt']) . '</span></div>[/et_pb_text]';

	$text_col = '[et_pb_column type="1_2" _builder_version="4.27.4" _module_preset="default"][et_pb_text _builder_version="4.27.4" _module_preset="default" module_class="as-prose as-programs-service-copy" ' . $text_style . ']' . $svc['body'] . '[/et_pb_text][/et_pb_column]';
	$img_col = '[et_pb_column type="1_2" _builder_version="4.27.4" _module_preset="default"]' . $image . '[/et_pb_column]';
	$cols = $reverse ? ($text_col . $img_col) : ($img_col . $text_col);

	return '[et_pb_section fb_built="1" _builder_version="4.27.4" _module_preset="default" custom_padding="1.5rem|0px|1.5rem|0px|false|false" background_color="RGBA(255,255,255,0)" module_class="as-programs-service"][et_pb_row _builder_version="4.27.4" _module_preset="default" max_width="80rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false" column_structure="1_2,1_2" use_custom_gutter="on" gutter_width="2"]' . $cols . '[/et_pb_row][/et_pb_section]';
}
