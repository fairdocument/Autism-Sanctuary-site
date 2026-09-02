<?php
/**
 * Pilot: rebuild /about/ with native Divi modules (Text + Image), not Code HTML.
 * Also fixes known copy typos (Missio/Visio truncation, Edgefield lede grammar).
 *
 * Run: wp eval-file wordpress-migration/pilot-about-native-divi.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

echo "=== Pilot: About → native Divi ===\n";

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
$aerial_id = as_about_attachment_id('edgefield-aerial.jpg');

$our_home_p1 = 'Autism Sanctuary operates from Edgefield—an extraordinary property and house stewarded by Frances Lee-Vandell. A 1780 home built by William Watkins was dismantled board by board and rebuilt onsite.';
$our_home_p2 = 'Today, the property features a range of animals, a robust garden, walking trails, and a variety of activity stations. Thanks to Frances\' incredible generosity, this property and land have become the foundation of our programs and have changed the lives of many.';

$mvv_text = <<<'HTML'
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
HTML;

$story_text = <<<'HTML'
<h2>Our Story</h2>
<p>Founded in 2020, Autism Sanctuary began as a vision to create a place where individuals with developmental disabilities could connect with nature, build community, and experience meaningful growth.</p>
<p>The Brewster family saw an opportunity for inclusive, nature-based programming for their high-support-needs son and others like him who lacked access to community opportunities and services. Working with their neighbor, Frances Lee-Vandell, their collective vision led to a living sanctuary that offers support, purpose, and belonging.</p>
<p>In December 2023, Autism Sanctuary became a Virginia DBHDS-licensed service provider. Since then, we have grown to meet the needs of our community and now offer day programs, residential services, community-based supports, and workplace assistance. In 2025, Autism Sanctuary received the Charlottesville Business Innovation Council Social Impact Award.</p>
<p>As we began serving families, we continued to identify gaps in services and expand our programs and offerings to meet the diverse needs of individuals and families in multiple ways. We now offer off-site residential and community-based services in addition to our on-site day program—fostering meaningful engagement through nature-based skill-building activities.</p>
HTML;

$edgefield_text = <<<HTML
<p>{$our_home_p1}</p>
<p class="as-lede--follow">{$our_home_p2}</p>
<p><a href="/our-farm/">See the farm &amp; trails</a> · <a href="/programs/">View services</a> · <a href="/contact/">Contact us</a></p>
HTML;

$text_style = 'text_font="Source Sans 3||||||||" text_text_color="#4A534C" header_font="Cormorant Garamond||||||||" header_text_color="#1E3D2C"';

$image_attrs = '';
if ($aerial) {
	$image_attrs = ' src="' . esc_url($aerial) . '" alt="Aerial view of the Edgefield property and surrounding land." title="Edgefield aerial" show_in_lightbox="off"';
	if ($aerial_id) {
		$image_attrs .= ' src_webp="' . esc_url($aerial) . '"';
	}
}

$content = <<<HTML
[et_pb_section fb_built="1" _builder_version="4.27.4" _module_preset="default" custom_padding="4rem|0px|2.5rem|0px|false|false" background_color="RGBA(255,255,255,0)" module_class="as-banner"][et_pb_row _builder_version="4.27.4" _module_preset="default" custom_padding="0px|0px|0px|0px|false|false" max_width="72rem"][et_pb_column type="4_4" _builder_version="4.27.4" _module_preset="default"][et_pb_text _builder_version="4.27.4" _module_preset="default" module_class="as-about-banner" {$text_style}]
<p class="as-eyebrow">About Autism Sanctuary</p>
<h1>A farm-based program where people with developmental disabilities are seen, supported, and busy outdoors.</h1>
<p class="as-lede">We pair passion and commitment with nature to create a therapeutic and active environment.</p>
[/et_pb_text][/et_pb_column][/et_pb_row][/et_pb_section][et_pb_section fb_built="1" _builder_version="4.27.4" _module_preset="default" custom_padding="0px|0px|3rem|0px|false|false" background_color="RGBA(255,255,255,0)" module_class="as-about-mvv"][et_pb_row _builder_version="4.27.4" _module_preset="default" max_width="80rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false" column_structure="1_2,1_2" use_custom_gutter="on" gutter_width="2"][et_pb_column type="1_2" _builder_version="4.27.4" _module_preset="default"][et_pb_text _builder_version="4.27.4" _module_preset="default" module_class="as-prose as-about-mvv-text" {$text_style}]
{$mvv_text}
[/et_pb_text][/et_pb_column][et_pb_column type="1_2" _builder_version="4.27.4" _module_preset="default"][et_pb_text _builder_version="4.27.4" _module_preset="default" module_class="as-about-mvv-photo" {$text_style}]
<div class="as-img-placeholder" role="img" aria-label="Photo placeholder: mission, vision, and values"><span>Photo placeholder: mission, vision, and values</span></div>
[/et_pb_text][/et_pb_column][/et_pb_row][/et_pb_section][et_pb_section fb_built="1" _builder_version="4.27.4" _module_preset="default" custom_padding="0px|0px|3rem|0px|false|false" background_color="RGBA(255,255,255,0)" module_class="as-about-story"][et_pb_row _builder_version="4.27.4" _module_preset="default" max_width="44rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false"][et_pb_column type="4_4" _builder_version="4.27.4" _module_preset="default"][et_pb_text _builder_version="4.27.4" _module_preset="default" module_class="as-prose" {$text_style}]
{$story_text}
[/et_pb_text][/et_pb_column][/et_pb_row][/et_pb_section][et_pb_section fb_built="1" _builder_version="4.27.4" _module_preset="default" custom_padding="0px|0px|4.5rem|0px|false|false" background_color="RGBA(255,255,255,0)" module_class="as-about-edgefield"][et_pb_row _builder_version="4.27.4" _module_preset="default" max_width="44rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false"][et_pb_column type="4_4" _builder_version="4.27.4" _module_preset="default"][et_pb_text _builder_version="4.27.4" _module_preset="default" module_class="as-prose" {$text_style}]
<h2>Our Home: Edgefield in White Hall, Virginia</h2>
[/et_pb_text][et_pb_image _builder_version="4.27.4" _module_preset="default" module_class="as-about-edgefield-photo" {$image_attrs} /][et_pb_text _builder_version="4.27.4" _module_preset="default" module_class="as-prose" {$text_style}]
{$edgefield_text}
[/et_pb_text][/et_pb_column][/et_pb_row][/et_pb_section]
HTML;

wp_update_post([
	'ID'           => $page->ID,
	'post_content' => $content,
	'post_excerpt' => 'A farm-based program where people with developmental disabilities are seen, supported, and busy outdoors.',
]);

update_post_meta($page->ID, '_et_pb_use_builder', 'on');
update_post_meta($page->ID, '_et_pb_page_layout', 'et_no_sidebar');
update_post_meta($page->ID, '_et_pb_show_title', 'off');
update_post_meta($page->ID, '_et_builder_version', '4.27.4');

if (function_exists('wp_cache_flush')) {
	wp_cache_flush();
}

$saved = get_post_field('post_content', $page->ID);
$codes = substr_count($saved, 'wp:divi/code') + substr_count($saved, 'et_pb_code');
$texts = substr_count($saved, 'wp:divi/text') + substr_count($saved, 'et_pb_text');
$images = substr_count($saved, 'wp:divi/image') + substr_count($saved, 'et_pb_image');

echo "OK /about/ (#{$page->ID}) native modules: text≈{$texts} image≈{$images} code≈{$codes}\n";
echo "Typos fixed: Mission, Vision, Thanks to Frances…\n";
echo "=== Done ===\n";

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

function as_about_attachment_id($filename) {
	$q = new WP_Query([
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => 200,
		'fields'         => 'ids',
	]);
	foreach ($q->posts as $id) {
		if (basename(get_attached_file($id)) === $filename) {
			return (int) $id;
		}
	}
	return 0;
}
