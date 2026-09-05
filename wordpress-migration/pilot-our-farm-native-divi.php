<?php
/**
 * Rebuild /our-farm/ with native Divi 5 Text + Image modules.
 *
 * Run: wp eval-file wordpress-migration/pilot-our-farm-native-divi.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

if (!class_exists('\\ET\\Builder\\Packages\\Conversion\\Conversion')) {
	echo "Divi 5 Conversion API not available.\n";
	return;
}

echo "=== Pilot: Our farm → native Divi 5 ===\n";

$admins = get_users(['role' => 'administrator', 'number' => 1]);
if ($admins) {
	wp_set_current_user((int) $admins[0]->ID);
}

\ET\Builder\Packages\Conversion\Conversion::initialize_shortcode_framework();

$page = get_page_by_path('our-farm');
if (!$page) {
	echo "Our farm page missing\n";
	return;
}

$backup_dir = WP_CONTENT_DIR . '/as-our-farm-native-backup-' . gmdate('Ymd-His');
wp_mkdir_p($backup_dir);
file_put_contents("{$backup_dir}/our-farm.raw.txt", $page->post_content);
echo "Backup: {$backup_dir}\n";

$aerial = as_farm_media('edgefield-aerial.jpg');
$tunnel = as_farm_media('IMG_9729-scaled.jpg');
if (!$tunnel) {
	$tunnel = as_farm_media('farm-animals.jpg');
}
$forest = as_farm_media('hero-enchanted-forest.jpg');

$text_style = 'text_font="Source Sans 3||||||||" text_text_color="#4A534C" header_font="Cormorant Garamond||||||||" header_text_color="#1E3D2C" custom_margin="||0px||false|false" custom_padding="0px|0px|0px|0px|false|false"';

$banner_html = <<<'HTML'
<div class="as-page">
<p class="as-eyebrow">Pea Ridge Road · Western Albemarle</p>
<h1>Our home—where the farm is the program.</h1>
<p class="as-lede">Roughly 85 acres of trails, animals, gardens, and activity stations, woven into the Blue Ridge Mountains. Licensed supports are integrated with hands-on skill-building, meaningful engagement, and a strong sense of purpose.</p>
</div>
HTML;

$why_html = <<<'HTML'
<div class="as-page as-prose as-farm-copy">
<h2>Why this place matters</h2>
<p>Our operating farm offers meaningful engagement and skill development grounded in nature and farming practices. Activities include immersive trails that encourage movement and exploration, gardens that support therapeutic skill-building, animal care opportunities, and a range of hands-on activities designed to build confidence, independence, practical skills, and a deeper connection with nature.</p>
</div>
HTML;

$animals_html = <<<'HTML'
<div class="as-page as-prose as-farm-copy">
<h2>Animals &amp; agriculture</h2>
<h3>Cattle</h3>
<p>Our cattle move regularly between pastures, enjoying days in the sun and plenty of room to explore. People in our program visit the herd regularly, offering gentle pets, scratches, and snacks while building familiarity and connection. After a long, happy life, our grass-fed cattle are processed into beef made available directly from our farm and through local partners and our farmers market tables.</p>
<h3>Chickens</h3>
<p>Chickens roam fields in warmer weather and stay in the fenced garden in winter. Caring for them helps teach empathy—and their eggs appear at farmers market visits.</p>
<h3>Bees</h3>
<p>Our hives support pollination across the farm and give people in our program a chance to learn about beekeeping, hive care, and the role pollinators play in healthy land. Honey from our bees is shared at farmers market visits when available—a sweet connection between stewardship, agriculture, and community.</p>
<h3>High tunnel &amp; gardens</h3>
<p>Our 30′×72′ high tunnel extends the growing season and anchors garden work, composting, and hands-on horticulture that people can return to week after week.</p>
</div>
HTML;

$trails_html = <<<'HTML'
<div class="as-page as-prose as-farm-copy">
<h2>Trails: Enchanted Forest &amp; Spring Creek</h2>
<p>Immersing people in nature supports education, regulation, and development. We currently offer two walking trails on the property.</p>
<h3>Enchanted Forest Trail</h3>
<p>A ¾-mile loop through forested land—more groomed and less sloped, with learning stations that promote skill building and creativity. Realized with support from CACF’s Enriching Communities Grant and volunteer partners.</p>
<h3>Spring Creek Trail</h3>
<p>A longer route that diverges downhill along Spring Creek. Steeper and more challenging, with a peaceful, engaging walk beside the water.</p>
</div>
HTML;

$volunteer_html = <<<'HTML'
<div class="as-page as-prose as-farm-copy">
<h2>Volunteer with us</h2>
<p>Volunteers support trail maintenance (raking, clearing debris, marking invasive species), agriculture in the high tunnel and gardens, and—with training and a weekly commitment—assistance alongside staff in the adult day program.</p>
<p>Volunteer roles complement, never replace, paid DSP staffing. We match tasks to season, capacity, and safety.</p>
<p><a href="/contact/?intent=volunteer">Inquire about volunteering</a> · <a href="/contact/">Contact us</a> · <a href="/donate/">Support the farm</a></p>
</div>
HTML;

$shortcode = as_farm_text_section('as-banner', '4rem|0px|2.5rem|0px', '72rem', 'as-farm-banner', $banner_html, $text_style)
	. as_farm_block_section('as-farm-why', $why_html, $aerial, 'Aerial view of Edgefield gardens and surrounding land.', 'Edgefield aerial', $text_style)
	. as_farm_block_section('as-farm-animals', $animals_html, $tunnel, 'High tunnel on the Autism Sanctuary farm.', 'High tunnel', $text_style)
	. as_farm_block_section('as-farm-trails', $trails_html, $forest, 'Enchanted Forest trail at Autism Sanctuary.', 'Enchanted Forest', $text_style)
	. as_farm_text_section('as-farm-volunteer', '0px|0px|4.5rem|0px', '44rem', 'as-farm-copy', $volunteer_html, $text_style);

$converted = \ET\Builder\Packages\Conversion\Conversion::maybeConvertContent(
	$shortcode,
	true,
	(int) $page->ID,
	true
);

if (!$converted || strpos($converted, '<!-- wp:divi/') === false) {
	echo "FAIL /our-farm/ Divi 5 conversion\n";
	return;
}

if (!as_farm_blocks_ok(parse_blocks($converted))) {
	echo "FAIL /our-farm/ converted blocks missing expected modules\n";
	return;
}

wp_update_post([
	'ID'           => $page->ID,
	'post_content' => wp_slash($converted),
	'post_excerpt' => 'Our home—where the farm is the program.',
]);

update_post_meta($page->ID, '_et_pb_use_builder', 'on');
update_post_meta($page->ID, '_et_pb_page_layout', 'et_no_sidebar');
update_post_meta($page->ID, '_et_pb_show_title', 'off');
update_post_meta($page->ID, '_et_builder_version', '5.11.1');

if (function_exists('wp_cache_flush')) {
	wp_cache_flush();
}
if (class_exists('ET_Core_PageResource')) {
	ET_Core_PageResource::remove_static_resources('all', 'all');
}

$saved = get_post_field('post_content', $page->ID);
preg_match_all('/wp:divi\/(\w+)/', $saved, $m);
$counts = array_count_values($m[1]);
$legacy = substr_count($saved, '[et_pb_');
$figures = substr_count($saved, '<figure');
$img_mods = $counts['image'] ?? 0;

echo 'OK /our-farm/ (#' . $page->ID . ') modules: ' . wp_json_encode($counts)
	. " legacy_shortcodes={$legacy} figure_html={$figures} image_modules={$img_mods}\n";
echo "=== Done ===\n";

function as_farm_text_section($section_class, $padding, $row_max, $text_class, $html, $text_style) {
	return sprintf(
		'[et_pb_section fb_built="1" _builder_version="4.27.4" _module_preset="default" custom_padding="%1$s|false|false" background_color="RGBA(255,255,255,0)" module_class="%2$s"][et_pb_row _builder_version="4.27.4" _module_preset="default" custom_padding="0px|1.25rem|0px|1.25rem|false|false" max_width="%3$s"][et_pb_column type="4_4" _builder_version="4.27.4" _module_preset="default"][et_pb_text _builder_version="4.27.4" _module_preset="default" module_class="%4$s" %5$s]%6$s[/et_pb_text][/et_pb_column][/et_pb_row][/et_pb_section]',
		esc_attr($padding),
		esc_attr($section_class),
		esc_attr($row_max),
		esc_attr($text_class),
		$text_style,
		$html
	);
}

function as_farm_block_section($section_class, $html, $image_url, $alt, $title, $text_style) {
	$image = '';
	if ($image_url) {
		$image = '[et_pb_image _builder_version="4.27.4" _module_preset="default" module_class="as-native-image as-farm-photo" src="'
			. esc_url($image_url) . '" alt="' . esc_attr($alt) . '" title="' . esc_attr($title)
			. '" show_in_lightbox="off" align="center" /]';
	}

	return '[et_pb_section fb_built="1" _builder_version="4.27.4" _module_preset="default" custom_padding="0px|0px|3rem|0px|false|false" background_color="RGBA(255,255,255,0)" module_class="'
		. esc_attr($section_class)
		. '"][et_pb_row _builder_version="4.27.4" _module_preset="default" max_width="44rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false"][et_pb_column type="4_4" _builder_version="4.27.4" _module_preset="default"][et_pb_text _builder_version="4.27.4" _module_preset="default" module_class="as-farm-copy" '
		. $text_style . ']' . $html . '[/et_pb_text]' . $image . '[/et_pb_column][/et_pb_row][/et_pb_section]';
}

function as_farm_blocks_ok($blocks) {
	$has_text = false;
	$has_image = false;
	$walker = function ($items) use (&$walker, &$has_text, &$has_image) {
		foreach ($items as $block) {
			$name = $block['blockName'] ?? '';
			if ($name === 'divi/text') {
				$val = $block['attrs']['content']['innerContent']['desktop']['value'] ?? '';
				if (is_string($val) && strpos($val, '<') !== false) {
					$has_text = true;
				}
			}
			if ($name === 'divi/image') {
				$has_image = true;
			}
			if (!empty($block['innerBlocks'])) {
				$walker($block['innerBlocks']);
			}
		}
	};
	$walker($blocks);
	return $has_text && $has_image;
}

function as_farm_media($filename) {
	static $map = null;
	if ($map === null) {
		$map = [];
		$q = new WP_Query([
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 500,
			'fields'         => 'ids',
		]);
		foreach ($q->posts as $id) {
			$file = basename((string) get_attached_file($id));
			$map[$file] = wp_get_attachment_url($id);
		}
	}
	return $map[$filename] ?? '';
}
