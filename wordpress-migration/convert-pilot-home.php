<?php
/**
 * Pilot 3: Rebuild Home page (ID 532) with native Divi 5 modules.
 * Converts service cards to native Divi blurbs, buttons to native Divi button modules,
 * and media splits to native Divi 2-column rows.
 *
 * Run: wp eval-file wordpress-migration/convert-pilot-home.php
 */

if (!defined('ABSPATH')) {
    exit(1);
}

if (!class_exists('\\ET\\Builder\\Packages\\Conversion\\Conversion')) {
    echo "Divi 5 Conversion API not available.\n";
    return;
}

$page = get_page_by_path('home');
if (!$page) {
    $page = get_post(532);
}
if (!$page) {
    echo "Home page not found.\n";
    return;
}

$backup_dir = WP_CONTENT_DIR . '/as-backup-pilot-home-' . gmdate('Ymd-His');
wp_mkdir_p($backup_dir);
file_put_contents("{$backup_dir}/home.raw.txt", $page->post_content);
echo "Backup created at: {$backup_dir}\n";

\ET\Builder\Packages\Conversion\Conversion::initialize_shortcode_framework();

$img_cattle = 'https://www.autismsanctuary.org/wp-content/uploads/2026/09/IMG_2421-scaled.jpg';
$img_aerial = 'https://www.autismsanctuary.org/wp-content/uploads/2026/08/edgefield-aerial.jpg';
$img_looking = 'https://www.autismsanctuary.org/wp-content/uploads/2026/09/IMG_2230-scaled.jpeg';

$sc = '';

// =========================================================================
// 1. HERO SECTION
// =========================================================================
$sc .= '[et_pb_section fb_built="1" _builder_version="4.27.4" module_class="as-hero" custom_padding="5rem|0px|4rem|0px|false|false" background_color="RGBA(255,255,255,0)"]';
$sc .= '[et_pb_row _builder_version="4.27.4" max_width="72rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false"]';
$sc .= '[et_pb_column type="4_4" _builder_version="4.27.4"]';
$sc .= '[et_pb_text _builder_version="4.27.4" module_class="as-eyebrow" text_font="Source Sans 3||||||||" text_text_color="#C28B38" custom_margin="||0.5rem||false|false"]<p class="as-eyebrow">501(c)(3) · Virginia DBHDS-licensed · Western Albemarle</p>[/et_pb_text]';
$sc .= '[et_pb_heading title="A working farm in the foothills of the Blue Ridge where people with developmental disabilities are supported and empowered to grow in nature." _builder_version="4.27.4" title_font="Cormorant Garamond||||||||" title_text_color="#1E3D2C" title_font_size="3rem" title_line_height="1.2" custom_margin="||1.5rem||false|false" /]';
$sc .= '[/et_pb_column][/et_pb_row]';

// Hero Buttons
$sc .= '[et_pb_row _builder_version="4.27.4" max_width="72rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false" column_structure="1_4,1_4,1_2"]';
$sc .= '[et_pb_column type="1_4" _builder_version="4.27.4"]';
$sc .= '[et_pb_button button_url="#approach" button_text="Our approach" _builder_version="4.27.4" module_class="as-btn-primary" /]';
$sc .= '[/et_pb_column]';
$sc .= '[et_pb_column type="1_4" _builder_version="4.27.4"]';
$sc .= '[et_pb_button button_url="#services" button_text="Licensed services" _builder_version="4.27.4" module_class="as-btn-ghost" /]';
$sc .= '[/et_pb_column]';
$sc .= '[et_pb_column type="1_2" _builder_version="4.27.4"][/et_pb_column]';
$sc .= '[/et_pb_row][/et_pb_section]';

// =========================================================================
// 2. APPROACH SECTION
// =========================================================================
$sc .= '[et_pb_section fb_built="1" _builder_version="4.27.4" module_id="approach" module_class="as-section as-approach-section" custom_padding="4.5rem|0px|4.5rem|0px|false|false" background_color="RGBA(255,255,255,0)"]';
$sc .= '[et_pb_row _builder_version="4.27.4" max_width="80rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false" column_structure="1_2,1_2" use_custom_gutter="on" gutter_width="2"]';

$sc .= '[et_pb_column type="1_2" _builder_version="4.27.4"]';
$sc .= '[et_pb_text _builder_version="4.27.4" module_class="as-eyebrow" text_font="Source Sans 3||||||||" text_text_color="#C28B38" custom_margin="||0.5rem||false|false"]<p class="as-eyebrow">Our approach</p>[/et_pb_text]';
$sc .= '[et_pb_heading title="Purpose and belonging, rooted in nature." _builder_version="4.27.4" title_font="Cormorant Garamond||||||||" title_text_color="#1E3D2C" title_font_size="2.25rem" custom_margin="||1rem||false|false" /]';
$sc .= '[et_pb_text _builder_version="4.27.4" module_class="as-lede" text_font="Source Sans 3||||||||" text_text_color="#4A534C" text_font_size="1.15rem" custom_margin="||1.5rem||false|false"]<p class="as-lede">People in our program engage with trails, animals, gardens, and staff who care. Days are designed around a sense of purpose and belonging, with an emphasis on growth and meaningful relationships.</p>[/et_pb_text]';
$sc .= '[et_pb_button button_url="/about/" button_text="About Autism Sanctuary" _builder_version="4.27.4" module_class="as-btn-primary" /]';
$sc .= '[/et_pb_column]';

$sc .= '[et_pb_column type="1_2" _builder_version="4.27.4"]';
$sc .= sprintf('[et_pb_image src="%s" alt="Participant with cattle in pasture" _builder_version="4.27.4" border_radii="on|12px|12px|12px|12px" align="center" /]', esc_url($img_cattle));
$sc .= '[/et_pb_column]';

$sc .= '[/et_pb_row][/et_pb_section]';

// =========================================================================
// 3. SERVICES SECTION (5 Native Divi Cards)
// =========================================================================
$sc .= '[et_pb_section fb_built="1" _builder_version="4.27.4" module_id="services" module_class="as-section as-services-section" custom_padding="4.5rem|0px|4.5rem|0px|false|false" background_color="RGBA(255,255,255,0)"]';

// Section intro row
$sc .= '[et_pb_row _builder_version="4.27.4" max_width="80rem" custom_padding="0px|1.25rem|2rem|1.25rem|false|false" column_structure="2_3,1_3"]';
$sc .= '[et_pb_column type="2_3" _builder_version="4.27.4"]';
$sc .= '[et_pb_text _builder_version="4.27.4" module_class="as-eyebrow" text_font="Source Sans 3||||||||" text_text_color="#C28B38" custom_margin="||0.5rem||false|false"]<p class="as-eyebrow">Our services</p>[/et_pb_text]';
$sc .= '[et_pb_heading title="Licensed support rooted in connection, growth, and belonging." _builder_version="4.27.4" title_font="Cormorant Garamond||||||||" title_text_color="#1E3D2C" title_font_size="2.25rem" custom_margin="||1rem||false|false" /]';
$sc .= '[et_pb_text _builder_version="4.27.4" module_class="as-lede" text_font="Source Sans 3||||||||" text_text_color="#4A534C" text_font_size="1.15rem"]<p class="as-lede">Autism Sanctuary provides meaningful support through nature, community, and individualized services designed to help people thrive.</p>[/et_pb_text]';
$sc .= '[/et_pb_column]';
$sc .= '[et_pb_column type="1_3" _builder_version="4.27.4" custom_padding="2rem|0px|0px|0px|false|false"]';
$sc .= '[et_pb_button button_url="/programs/" button_text="Full service details" _builder_version="4.27.4" module_class="as-btn-primary" /]';
$sc .= '[/et_pb_column]';
$sc .= '[/et_pb_row]';

// Services Row 1: 3 cards
$sc .= '[et_pb_row _builder_version="4.27.4" max_width="80rem" custom_padding="0px|1.25rem|1.5rem|1.25rem|false|false" column_structure="1_3,1_3,1_3" use_custom_gutter="on" gutter_width="2"]';

$sc .= '[et_pb_column type="1_3" _builder_version="4.27.4"]';
$sc .= '[et_pb_blurb title="Group Day" _builder_version="4.27.4" module_class="as-native-service-card" use_icon="off"]<p>On-site weekday support on the farm—animal care, gardens, trails, and skill-building in nature.</p>[/et_pb_blurb]';
$sc .= '[/et_pb_column]';

$sc .= '[et_pb_column type="1_3" _builder_version="4.27.4"]';
$sc .= '[et_pb_blurb title="Community Coaching" _builder_version="4.27.4" module_class="as-native-service-card" use_icon="off"]<p>1:1 support in the community to build skills that open doors to everyday participation.</p>[/et_pb_blurb]';
$sc .= '[/et_pb_column]';

$sc .= '[et_pb_column type="1_3" _builder_version="4.27.4"]';
$sc .= '[et_pb_blurb title="Community Engagement" _builder_version="4.27.4" module_class="as-native-service-card" use_icon="off"]<p>1:3 small-group support for meaningful outings and community life beyond the farm.</p>[/et_pb_blurb]';
$sc .= '[/et_pb_column]';
$sc .= '[/et_pb_row]';

// Services Row 2: 2 cards
$sc .= '[et_pb_row _builder_version="4.27.4" max_width="80rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false" column_structure="1_2,1_2" use_custom_gutter="on" gutter_width="2"]';

$sc .= '[et_pb_column type="1_2" _builder_version="4.27.4"]';
$sc .= '[et_pb_blurb title="Residential & Home-Based" _builder_version="4.27.4" module_class="as-native-service-card" use_icon="off"]<p>Personalized supports in people’s homes and community settings where authorized.</p>[/et_pb_blurb]';
$sc .= '[/et_pb_column]';

$sc .= '[et_pb_column type="1_2" _builder_version="4.27.4"]';
$sc .= '[et_pb_blurb title="Workplace Assistance" _builder_version="4.27.4" module_class="as-native-service-card" use_icon="off"]<p>1:1 on-the-job support that helps people succeed in meaningful employment.</p>[/et_pb_blurb]';
$sc .= '[/et_pb_column]';
$sc .= '[/et_pb_row][/et_pb_section]';

// =========================================================================
// 4. OUR HOME SECTION (Edgefield)
// =========================================================================
$sc .= '[et_pb_section fb_built="1" _builder_version="4.27.4" module_class="as-section as-home-farm-section" custom_padding="4.5rem|0px|4.5rem|0px|false|false" background_color="RGBA(255,255,255,0)"]';
$sc .= '[et_pb_row _builder_version="4.27.4" max_width="80rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false" column_structure="1_2,1_2" use_custom_gutter="on" gutter_width="2"]';

$sc .= '[et_pb_column type="1_2" _builder_version="4.27.4"]';
$sc .= '[et_pb_text _builder_version="4.27.4" module_class="as-eyebrow" text_font="Source Sans 3||||||||" text_text_color="#C28B38" custom_margin="||0.5rem||false|false"]<p class="as-eyebrow">Our home</p>[/et_pb_text]';
$sc .= '[et_pb_heading title="Edgefield in the Blue Ridge foothills." _builder_version="4.27.4" title_font="Cormorant Garamond||||||||" title_text_color="#1E3D2C" title_font_size="2.25rem" custom_margin="||1rem||false|false" /]';
$sc .= '[et_pb_text _builder_version="4.27.4" module_class="as-lede" text_font="Source Sans 3||||||||" text_text_color="#4A534C" text_font_size="1.15rem" custom_margin="||1rem||false|false"]<p class="as-lede">Autism Sanctuary operates from Edgefield—an extraordinary property and house stewarded by Frances Lee-Vandell. A 1780 home built by William Watkins was dismantled board by board and rebuilt onsite.</p>[/et_pb_text]';
$sc .= '[et_pb_text _builder_version="4.27.4" text_font="Source Sans 3||||||||" text_text_color="#4A534C" custom_margin="||1.5rem||false|false"]<p>Today, the property features a range of animals, a robust garden, walking trails, and a variety of activity stations. Thanks to Frances’ incredible generosity, this property and land have become the foundation of our programs and have changed the lives of many.</p>[/et_pb_text]';
$sc .= '[et_pb_button button_url="/our-farm/" button_text="Explore the property" _builder_version="4.27.4" module_class="as-btn-primary" custom_margin="||0px|0px|false|false" /]';
$sc .= '[et_pb_button button_url="/contact/?intent=volunteer" button_text="Volunteer with us" _builder_version="4.27.4" module_class="as-btn-ghost" custom_margin="||0px|1rem|false|false" /]';
$sc .= '[/et_pb_column]';

$sc .= '[et_pb_column type="1_2" _builder_version="4.27.4"]';
$sc .= sprintf('[et_pb_image src="%s" alt="Edgefield property aerial view" _builder_version="4.27.4" border_radii="on|12px|12px|12px|12px" align="center" /]', esc_url($img_aerial));
$sc .= '[/et_pb_column]';

$sc .= '[/et_pb_row][/et_pb_section]';

// =========================================================================
// 5. LOOKING AHEAD SECTION
// =========================================================================
$sc .= '[et_pb_section fb_built="1" _builder_version="4.27.4" module_class="as-section as-looking-ahead-section" custom_padding="4.5rem|0px|4.5rem|0px|false|false" background_color="RGBA(255,255,255,0)"]';
$sc .= '[et_pb_row _builder_version="4.27.4" max_width="80rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false" column_structure="1_2,1_2" use_custom_gutter="on" gutter_width="2"]';

$sc .= '[et_pb_column type="1_2" _builder_version="4.27.4"]';
$sc .= sprintf('[et_pb_image src="%s" alt="Looking ahead at Autism Sanctuary" _builder_version="4.27.4" border_radii="on|12px|12px|12px|12px" align="center" /]', esc_url($img_looking));
$sc .= '[/et_pb_column]';

$sc .= '[et_pb_column type="1_2" _builder_version="4.27.4"]';
$sc .= '[et_pb_text _builder_version="4.27.4" module_class="as-eyebrow" text_font="Source Sans 3||||||||" text_text_color="#C28B38" custom_margin="||0.5rem||false|false"]<p class="as-eyebrow">Looking ahead</p>[/et_pb_text]';
$sc .= '[et_pb_heading title="Growing capacity for purpose and belonging." _builder_version="4.27.4" title_font="Cormorant Garamond||||||||" title_text_color="#1E3D2C" title_font_size="2.25rem" custom_margin="||1rem||false|false" /]';
$sc .= '[et_pb_text _builder_version="4.27.4" module_class="as-lede" text_font="Source Sans 3||||||||" text_text_color="#4A534C" text_font_size="1.15rem" custom_margin="||1rem||false|false"]<p class="as-lede">With your support, we are advancing capital and program priorities that strengthen life at Autism Sanctuary.</p>[/et_pb_text]';
$checklist_html = <<<HTML
<ul class="as-checklist">
  <li><strong>Activity Barn:</strong> a purpose-built program space that supports growth and moves day programming out of the historic house basement.</li>
  <li><strong>Facility upgrades:</strong> improvements to existing campus resources that keep outdoor and indoor days safe, welcoming, and ready for more people.</li>
  <li><strong>Workplace opportunities under development:</strong> expanding vocational pathways and Workplace Assistance so more people can build skills and meaningful work.</li>
</ul>
HTML;
$sc .= '[et_pb_text _builder_version="4.27.4" text_font="Source Sans 3||||||||" text_text_color="#4A534C" custom_margin="||1.5rem||false|false"]' . $checklist_html . '[/et_pb_text]';
$sc .= '[et_pb_button button_url="/donate/" button_text="Support this work" _builder_version="4.27.4" module_class="as-btn-primary" /]';
$sc .= '[/et_pb_column]';

$sc .= '[/et_pb_row][/et_pb_section]';

// =========================================================================
// 6. NEWSROOM SECTION (Divi Blog module)
// =========================================================================
$sc .= '[et_pb_section fb_built="1" _builder_version="4.27.4" module_class="as-section as-home-news-section" custom_padding="4.5rem|0px|4.5rem|0px|false|false" background_color="RGBA(255,255,255,0)"]';
$sc .= '[et_pb_row _builder_version="4.27.4" max_width="80rem" custom_padding="0px|1.25rem|2rem|1.25rem|false|false" column_structure="2_3,1_3"]';
$sc .= '[et_pb_column type="2_3" _builder_version="4.27.4"]';
$sc .= '[et_pb_text _builder_version="4.27.4" module_class="as-eyebrow" text_font="Source Sans 3||||||||" text_text_color="#C28B38" custom_margin="||0.5rem||false|false"]<p class="as-eyebrow">Newsroom</p>[/et_pb_text]';
$sc .= '[et_pb_heading title="Latest updates" _builder_version="4.27.4" title_font="Cormorant Garamond||||||||" title_text_color="#1E3D2C" title_font_size="2.25rem" custom_margin="||0.5rem||false|false" /]';
$sc .= '[et_pb_text _builder_version="4.27.4" module_class="as-lede" text_font="Source Sans 3||||||||" text_text_color="#4A534C"]<p class="as-lede">Farm stories, press, and program notes from Autism Sanctuary.</p>[/et_pb_text]';
$sc .= '[/et_pb_column]';
$sc .= '[et_pb_column type="1_3" _builder_version="4.27.4" custom_padding="2rem|0px|0px|0px|false|false"]';
$sc .= '[et_pb_button button_url="/news/" button_text="Read more news stories" _builder_version="4.27.4" module_class="as-btn-ghost" /]';
$sc .= '[/et_pb_column]';
$sc .= '[/et_pb_row]';

// Blog Module Row
$sc .= '[et_pb_row _builder_version="4.27.4" max_width="80rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false"][et_pb_column type="4_4" _builder_version="4.27.4"]';
$sc .= '[et_pb_blog fullwidth="off" posts_number="3" include_categories="all" meta_date="F j, Y" show_thumbnail="on" show_content="off" show_more="off" show_author="off" show_date="on" show_categories="off" show_comments="off" show_excerpt="on" excerpt_length="28" show_pagination="off" use_overlay="off" _builder_version="4.27.4" module_class="as-home-news-blog" header_level="h3" header_font="Cormorant Garamond||||||||" header_font_size="1.4rem" header_text_color="#1E3D2C" body_font="Source Sans 3||||||||" body_text_color="#4A534C" meta_font="Source Sans 3||||||||" meta_text_color="#4A534C" meta_font_size="0.9rem" border_width_all="0px" /]';
$sc .= '[/et_pb_column][/et_pb_row][/et_pb_section]';

// =========================================================================
// 7. GET IN TOUCH (CTA) SECTION
// =========================================================================
$sc .= '[et_pb_section fb_built="1" _builder_version="4.27.4" module_class="as-section as-cta-section" custom_padding="4.5rem|0px|5rem|0px|false|false" background_color="RGBA(255,255,255,0)"]';
$sc .= '[et_pb_row _builder_version="4.27.4" max_width="44rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false"][et_pb_column type="4_4" _builder_version="4.27.4"]';
$sc .= '[et_pb_heading title="Get in touch" _builder_version="4.27.4" title_font="Cormorant Garamond||||||||" title_text_color="#1E3D2C" title_font_size="2.5rem" title_line_height="1.2" custom_margin="||1.5rem||false|false" /]';
$sc .= '[et_pb_button button_url="/contact/" button_text="Contact us" _builder_version="4.27.4" module_class="as-btn-primary" custom_margin="||0px|0px|false|false" /]';
$sc .= '[et_pb_button button_url="/programs/" button_text="See programs" _builder_version="4.27.4" module_class="as-btn-ghost" custom_margin="||0px|1rem|false|false" /]';
$sc .= '[/et_pb_column][/et_pb_row][/et_pb_section]';

// Convert to Divi 5
$converted = \ET\Builder\Packages\Conversion\Conversion::maybeConvertContent(
    $sc,
    true,
    (int) $page->ID,
    true
);

if (!$converted || strpos($converted, '<!-- wp:divi/') === false) {
    echo "ERROR: Divi 5 conversion failed for Home.\n";
    return;
}

wp_update_post([
    'ID'           => $page->ID,
    'post_content' => wp_slash($converted),
]);

update_post_meta($page->ID, '_et_pb_use_builder', 'on');
update_post_meta($page->ID, '_et_pb_page_layout', 'et_no_sidebar');
update_post_meta($page->ID, '_et_pb_show_title', 'off');
update_post_meta($page->ID, '_et_builder_version', '5.11.1');

if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
}

$saved = get_post_field('post_content', $page->ID);
preg_match_all('/wp:divi\/([a-z0-9_-]+)/', $saved, $m);
$counts = array_count_values($m[1]);
echo "SUCCESS: Rebuilt Home page with 100% native Divi 5 modules!\n";
echo "Module inventory: " . json_encode($counts) . "\n";
