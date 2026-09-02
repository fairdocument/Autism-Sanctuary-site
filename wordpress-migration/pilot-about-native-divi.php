<?php
/**
 * Pilot: rebuild /about/ with native Divi Text/Image modules (Divi 5).
 * Wraps copy in as-page / as-prose containers for brand typography.
 *
 * Run: wp eval-file wordpress-migration/pilot-about-native-divi.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

if (!class_exists('\\ET\\Builder\\Packages\\Conversion\\Conversion')) {
	echo "Divi 5 Conversion API not available.\n";
	return;
}

echo "=== Pilot: About → native Divi 5 ===\n";

$admins = get_users(['role' => 'administrator', 'number' => 1]);
if ($admins) {
	wp_set_current_user((int) $admins[0]->ID);
}

\ET\Builder\Packages\Conversion\Conversion::initialize_shortcode_framework();

$page = get_page_by_path('about');
if (!$page) {
	echo "About page missing\n";
	return;
}

$backup_dir = WP_CONTENT_DIR . '/as-about-native-backup-' . gmdate('Ymd-His');
wp_mkdir_p($backup_dir);
file_put_contents("{$backup_dir}/about.raw.txt", $page->post_content);
echo "Backup: {$backup_dir}\n";

$aerial = as_about_media('edgefield-aerial.jpg');

$our_home_p1 = 'Autism Sanctuary operates from Edgefield—an extraordinary property and house stewarded by Frances Lee-Vandell. A 1780 home built by William Watkins was dismantled board by board and rebuilt onsite.';
$our_home_p2 = 'Today, the property features a range of animals, a robust garden, walking trails, and a variety of activity stations. Thanks to Frances\' incredible generosity, this property and land have become the foundation of our programs and have changed the lives of many.';

$banner_html = <<<'HTML'
<div class="as-page">
<p class="as-eyebrow">About Autism Sanctuary</p>
<h1>A farm-based program where people with developmental disabilities are seen, supported, and busy outdoors.</h1>
<p class="as-lede">We pair passion and commitment with nature to create a therapeutic and active environment.</p>
</div>
HTML;

$mvv_html = <<<HTML
<div class="as-prose as-about-mvv-text">
<h2>Mission</h2>
<p>To enhance the lives of those with developmental disabilities through nature-based activities by fostering community connections and providing personalized support.</p>
<h2>Vision</h2>
<p>A world where each person with developmental disabilities can find a sense of belonging and community while receiving the supports they need to live a meaningful life.</p>
<h2>Values — BEE NICE</h2>
<ul class="as-checklist">
<li><strong>Belonging:</strong> Creating a welcoming environment where everyone feels valued and accepted.</li>
<li><strong>Enrichment:</strong> Providing opportunities for growth, learning, and development through engaging activities and experiences.</li>
<li><strong>Enthusiasm:</strong> We are excited about the work we do.</li>
<li><strong>Nature:</strong> Promoting peace and tranquility with the benefits of nature, including trees, animals, plants, creeks, and trails to enhance well-being.</li>
<li><strong>Integrity:</strong> Upholding the highest standards of honesty, respect, and dignity in all interactions and practices.</li>
<li><strong>Connection — Community:</strong> Fostering meaningful connections within the community to build a supportive network.</li>
<li><strong>Connection — Collaboration:</strong> Working together with families, volunteers, and partners to achieve common goals.</li>
<li><strong>Excellence:</strong> Continuously striving for the highest quality in services and support for individuals with autism using proven and new methods.</li>
</ul>
</div>
HTML;

$mvv_photo_html = <<<'HTML'
<div class="as-about-mvv-photo">
<div class="as-img-placeholder" role="img" aria-label="Photo placeholder: mission, vision, and values"><span>Photo placeholder: mission, vision, and values</span></div>
</div>
HTML;

$story_html = <<<'HTML'
<div class="as-page as-prose">
<h2>Our Story</h2>
<p>Founded in 2020, Autism Sanctuary began as a vision to create a place where individuals with developmental disabilities could connect with nature, build community, and experience meaningful growth.</p>
<p>The Brewster family saw an opportunity for inclusive, nature-based programming for their high-support-needs son and others like him who lacked access to community opportunities and services. Working with their neighbor, Frances Lee-Vandell, their collective vision led to a living sanctuary that offers support, purpose, and belonging.</p>
<p>In December 2023, Autism Sanctuary became a Virginia DBHDS-licensed service provider. Since then, we have grown to meet the needs of our community and now offer day programs, residential services, community-based supports, and workplace assistance. In 2025, Autism Sanctuary received the Charlottesville Business Innovation Council Social Impact Award.</p>
<p>As we began serving families, we continued to identify gaps in services and expand our programs and offerings to meet the diverse needs of individuals and families in multiple ways. We now offer off-site residential and community-based services in addition to our on-site day program—fostering meaningful engagement through nature-based skill-building activities.</p>
</div>
HTML;

$edgefield_heading_html = <<<'HTML'
<div class="as-page as-prose as-about-edgefield-heading">
<h2>Our Home: Edgefield in White Hall, Virginia</h2>
</div>
HTML;

$edgefield_body_html = <<<HTML
<div class="as-page as-prose as-about-edgefield-body">
<p>{$our_home_p1}</p>
<p class="as-lede--follow">{$our_home_p2}</p>
<p><a href="/our-farm/">See the farm &amp; trails</a> · <a href="/programs/">View services</a> · <a href="/contact/">Contact us</a></p>
</div>
HTML;

$text_style = 'text_font="Source Sans 3||||||||" text_text_color="#4A534C" header_font="Cormorant Garamond||||||||" header_text_color="#1E3D2C" custom_margin="||0px||false|false" custom_padding="0px|0px|0px|0px|false|false"';

$image_attrs = $aerial
	? ' src="' . esc_url($aerial) . '" alt="Aerial view of the Edgefield property and surrounding land." title="Edgefield aerial" show_in_lightbox="off" align="center"'
	: '';

$shortcode = as_about_text_section('as-banner', '4rem|0px|2.5rem|0px', '72rem', '4_4', 'as-about-banner', $banner_html, $text_style)
	. as_about_mvv_section($mvv_html, $mvv_photo_html, $text_style)
	. as_about_text_section('as-about-story', '0px|0px|3rem|0px', '44rem', '4_4', 'as-about-story-copy', $story_html, $text_style)
	. as_about_edgefield_section($edgefield_heading_html, $edgefield_body_html, $image_attrs, $text_style);

$converted = \ET\Builder\Packages\Conversion\Conversion::maybeConvertContent(
	$shortcode,
	true,
	(int) $page->ID,
	true
);

if (!$converted || strpos($converted, '<!-- wp:divi/') === false) {
	echo "FAIL /about/ Divi 5 conversion\n";
	return;
}

if (!as_about_blocks_have_html(parse_blocks($converted))) {
	echo "FAIL /about/ converted blocks missing HTML\n";
	return;
}

wp_update_post([
	'ID'           => $page->ID,
	'post_content' => wp_slash($converted),
	'post_excerpt' => 'A farm-based program where people with developmental disabilities are seen, supported, and busy outdoors.',
]);

update_post_meta($page->ID, '_et_pb_use_builder', 'on');
update_post_meta($page->ID, '_et_pb_page_layout', 'et_no_sidebar');
update_post_meta($page->ID, '_et_pb_show_title', 'off');
update_post_meta($page->ID, '_et_builder_version', '5.0.0');

if (function_exists('wp_cache_flush')) {
	wp_cache_flush();
}

$saved = get_post_field('post_content', $page->ID);
preg_match_all('/wp:divi\/(\w+)/', $saved, $m);
$counts = array_count_values($m[1]);
$legacy = substr_count($saved, '[et_pb_');

echo 'OK /about/ (#' . $page->ID . ') modules: ' . wp_json_encode($counts) . " legacy_shortcodes={$legacy}\n";
echo "=== Done ===\n";

function as_about_text_section($section_class, $padding, $row_max, $col_type, $text_class, $html, $text_style) {
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

function as_about_mvv_section($left_html, $right_html, $text_style) {
	return '[et_pb_section fb_built="1" _builder_version="4.27.4" _module_preset="default" custom_padding="0px|0px|3rem|0px|false|false" background_color="RGBA(255,255,255,0)" module_class="as-about-mvv"][et_pb_row _builder_version="4.27.4" _module_preset="default" max_width="80rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false" column_structure="1_2,1_2" use_custom_gutter="on" gutter_width="2"][et_pb_column type="1_2" _builder_version="4.27.4" _module_preset="default"][et_pb_text _builder_version="4.27.4" _module_preset="default" module_class="as-about-mvv-copy" ' . $text_style . ']' . $left_html . '[/et_pb_text][/et_pb_column][et_pb_column type="1_2" _builder_version="4.27.4" _module_preset="default"][et_pb_text _builder_version="4.27.4" _module_preset="default" module_class="as-about-mvv-photo-wrap" ' . $text_style . ']' . $right_html . '[/et_pb_text][/et_pb_column][/et_pb_row][/et_pb_section]';
}

function as_about_edgefield_section($heading_html, $body_html, $image_attrs, $text_style) {
	$image = $image_attrs
		? '[et_pb_image _builder_version="4.27.4" _module_preset="default" module_class="as-about-edgefield-photo"' . $image_attrs . ' /]'
		: '';

	return '[et_pb_section fb_built="1" _builder_version="4.27.4" _module_preset="default" custom_padding="0px|0px|4.5rem|0px|false|false" background_color="RGBA(255,255,255,0)" module_class="as-about-edgefield"][et_pb_row _builder_version="4.27.4" _module_preset="default" max_width="44rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false"][et_pb_column type="4_4" _builder_version="4.27.4" _module_preset="default"][et_pb_text _builder_version="4.27.4" _module_preset="default" module_class="as-about-edgefield-heading" ' . $text_style . ']' . $heading_html . '[/et_pb_text]' . $image . '[et_pb_text _builder_version="4.27.4" _module_preset="default" module_class="as-about-edgefield-body" ' . $text_style . ']' . $body_html . '[/et_pb_text][/et_pb_column][/et_pb_row][/et_pb_section]';
}

function as_about_blocks_have_html($blocks) {
	foreach ($blocks as $block) {
		$val = $block['attrs']['content']['innerContent']['desktop']['value'] ?? '';
		if (is_string($val) && strpos($val, '<') !== false) {
			return true;
		}
		if (!empty($block['innerBlocks']) && as_about_blocks_have_html($block['innerBlocks'])) {
			return true;
		}
	}
	return false;
}

function as_about_media($filename) {
	static $map = null;
	if ($map === null) {
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
	}
	return $map[$filename] ?? '';
}
