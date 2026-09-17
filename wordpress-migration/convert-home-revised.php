<?php
/**
 * Convert Home Revised (ID 692) to 100% Native Divi 5 Modules
 *
 * Combines:
 * 1. Sections 1-4 & 6 converted via Divi 5 maybeConvertContent()
 *    ensuring all native button, blurb, image, and heading attributes are fully preserved.
 * 2. Native Section 0 Hero from post 532 (already 100% native Divi 5 video/image hero).
 * 3. Section 5 Newsroom using native Divi 5 Blog module from post 532.
 *
 * Strictly scoped to Page 692 (/home-revised/). Never modifies live ID 532.
 */

if (!defined('ABSPATH')) {
    require_once dirname(__DIR__) . '/wp-load.php';
}

echo "=== CONVERTING HOME REVISED (PAGE 692) VIA DIVI 5 NATIVE CONVERSION ===\n";

global $wpdb;

$live_home = get_post(532);
if (!$live_home) {
    die("Error: Live home post 532 not found.\n");
}

$revised_home = get_post(692);
if (!$revised_home) {
    die("Error: Revised home post 692 not found.\n");
}

\ET\Builder\Packages\Conversion\Conversion::initialize_shortcode_framework();

// 1. Convert Section 1: Our Approach
$sc1 = <<<SC
[et_pb_section fb_built="1" _builder_version="4.27.4" module_id="approach" module_class="as-section as-approach-section" custom_padding="4.5rem|0px|4.5rem|0px|false|false" background_color="RGBA(255,255,255,0)"]
  [et_pb_row _builder_version="4.27.4" max_width="80rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false" column_structure="1_2,1_2" use_custom_gutter="on" gutter_width="2"]
    [et_pb_column type="1_2" _builder_version="4.27.4"]
      [et_pb_image src="https://www.autismsanctuary.org/wp-content/uploads/2026/08/sanctuary-gardens.jpg" alt="Garden paths and plantings at Autism Sanctuary farm." _builder_version="4.27.4" module_class="as-native-image" border_radii="on|16px|16px|16px|16px" align="center" /]
    [/et_pb_column]
    [et_pb_column type="1_2" _builder_version="4.27.4"]
      [et_pb_text _builder_version="4.27.4" module_class="as-eyebrow" text_font="Source Sans 3||||||||" text_text_color="#C28B38" custom_margin="||0.5rem||false|false"]<p class="as-eyebrow">Our approach</p>[/et_pb_text]
      [et_pb_heading title="Purpose and belonging, rooted in nature." _builder_version="4.27.4" title_font="Cormorant Garamond||||||||" title_text_color="#1E3D2C" title_font_size="2.25rem" custom_margin="||1rem||false|false" /]
      [et_pb_text _builder_version="4.27.4" module_class="as-lede" text_font="Source Sans 3||||||||" text_text_color="#4A534C" text_font_size="1.15rem" custom_margin="||1.5rem||false|false"]<p class="as-lede">People in our program engage with trails, animals, gardens, and staff who care. Days are designed around a sense of purpose and belonging, with an emphasis on growth and meaningful relationships.</p>[/et_pb_text]
      [et_pb_button button_url="/about/" button_text="About Autism Sanctuary" _builder_version="4.27.4" module_class="as-btn-primary" /]
    [/et_pb_column]
  [/et_pb_row]
[/et_pb_section]
SC;
$r1 = \ET\Builder\Packages\Conversion\Conversion::maybeConvertContent($sc1, true, 692, true);
echo "Section 1 (Approach) converted: " . strlen($r1) . " bytes\n";

// 2. Convert Section 2: Our Services
$sc2 = <<<SC
[et_pb_section fb_built="1" _builder_version="4.27.4" module_id="services" module_class="as-section as-services-section" custom_padding="4.5rem|0px|4.5rem|0px|false|false" background_color="#f5f2e8"]
  [et_pb_row _builder_version="4.27.4" max_width="80rem" custom_padding="0px|1.25rem|2rem|1.25rem|false|false" column_structure="2_3,1_3"]
    [et_pb_column type="2_3" _builder_version="4.27.4"]
      [et_pb_text _builder_version="4.27.4" module_class="as-eyebrow" text_font="Source Sans 3||||||||" text_text_color="#C28B38" custom_margin="||0.5rem||false|false"]<p class="as-eyebrow">Our services</p>[/et_pb_text]
      [et_pb_heading title="Licensed support rooted in connection, growth, and belonging." _builder_version="4.27.4" title_font="Cormorant Garamond||||||||" title_text_color="#1E3D2C" title_font_size="2.25rem" custom_margin="||1rem||false|false" /]
      [et_pb_text _builder_version="4.27.4" module_class="as-lede" text_font="Source Sans 3||||||||" text_text_color="#4A534C" text_font_size="1.15rem"]<p class="as-lede">Autism Sanctuary provides meaningful support through nature, community, and individualized services designed to help people thrive.</p>[/et_pb_text]
    [/et_pb_column]
    [et_pb_column type="1_3" _builder_version="4.27.4" custom_padding="2rem|0px|0px|0px|false|false"]
      [et_pb_button button_url="/programs/" button_text="Full service details" _builder_version="4.27.4" module_class="as-btn-primary" /]
    [/et_pb_column]
  [/et_pb_row]
  [et_pb_row _builder_version="4.27.4" max_width="80rem" custom_padding="0px|1.25rem|1.5rem|1.25rem|false|false" column_structure="1_3,1_3,1_3" use_custom_gutter="on" gutter_width="2"]
    [et_pb_column type="1_3" _builder_version="4.27.4"]
      [et_pb_blurb title="Group Day" _builder_version="4.27.4" module_class="as-native-service-card" use_icon="off"]<p>On-site weekday support on the farm—animal care, gardens, trails, and skill-building in nature.</p>[/et_pb_blurb]
    [/et_pb_column]
    [et_pb_column type="1_3" _builder_version="4.27.4"]
      [et_pb_blurb title="Community Coaching" _builder_version="4.27.4" module_class="as-native-service-card" use_icon="off"]<p>1:1 support in the community to build skills that open doors to everyday participation.</p>[/et_pb_blurb]
    [/et_pb_column]
    [et_pb_column type="1_3" _builder_version="4.27.4"]
      [et_pb_blurb title="Community Engagement" _builder_version="4.27.4" module_class="as-native-service-card" use_icon="off"]<p>1:3 small-group support for meaningful outings and community life beyond the farm.</p>[/et_pb_blurb]
    [/et_pb_column]
  [/et_pb_row]
  [et_pb_row _builder_version="4.27.4" max_width="80rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false" column_structure="1_2,1_2" use_custom_gutter="on" gutter_width="2"]
    [et_pb_column type="1_2" _builder_version="4.27.4"]
      [et_pb_blurb title="Residential & Home-Based" _builder_version="4.27.4" module_class="as-native-service-card" use_icon="off"]<p>Personalized supports in people’s homes and community settings where authorized.</p>[/et_pb_blurb]
    [/et_pb_column]
    [et_pb_column type="1_2" _builder_version="4.27.4"]
      [et_pb_blurb title="Workplace Assistance" _builder_version="4.27.4" module_class="as-native-service-card" use_icon="off"]<p>1:1 on-the-job support that helps people succeed in meaningful employment.</p>[/et_pb_blurb]
    [/et_pb_column]
  [/et_pb_row]
[/et_pb_section]
SC;
$r2 = \ET\Builder\Packages\Conversion\Conversion::maybeConvertContent($sc2, true, 692, true);
echo "Section 2 (Services) converted: " . strlen($r2) . " bytes\n";

// 3. Convert Section 3: Our Home
$sc3 = <<<SC
[et_pb_section fb_built="1" _builder_version="4.27.4" module_class="as-section as-home-farm-section" custom_padding="4.5rem|0px|4.5rem|0px|false|false" background_color="RGBA(255,255,255,0)"]
  [et_pb_row _builder_version="4.27.4" max_width="80rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false" column_structure="1_2,1_2" use_custom_gutter="on" gutter_width="2"]
    [et_pb_column type="1_2" _builder_version="4.27.4"]
      [et_pb_text _builder_version="4.27.4" module_class="as-eyebrow" text_font="Source Sans 3||||||||" text_text_color="#C28B38" custom_margin="||0.5rem||false|false"]<p class="as-eyebrow">Our home</p>[/et_pb_text]
      [et_pb_heading title="Edgefield in the Blue Ridge foothills." _builder_version="4.27.4" title_font="Cormorant Garamond||||||||" title_text_color="#1E3D2C" title_font_size="2.25rem" custom_margin="||1rem||false|false" /]
      [et_pb_text _builder_version="4.27.4" module_class="as-lede" text_font="Source Sans 3||||||||" text_text_color="#4A534C" text_font_size="1.15rem" custom_margin="||1rem||false|false"]<p class="as-lede">Autism Sanctuary operates from Edgefield—an extraordinary property and house stewarded by Frances Lee-Vandell. A 1780 home built by William Watkins was dismantled board by board and rebuilt onsite.</p>[/et_pb_text]
      [et_pb_text _builder_version="4.27.4" text_font="Source Sans 3||||||||" text_text_color="#4A534C" custom_margin="||1.5rem||false|false"]<p>Today, the property features a range of animals, a robust garden, walking trails, and a variety of activity stations. Thanks to Frances’ incredible generosity, this property and land have become the foundation of our programs and have changed the lives of many.</p>[/et_pb_text]
      [et_pb_button button_url="/our-farm/" button_text="Explore the property" _builder_version="4.27.4" module_class="as-btn-primary" custom_margin="||0px|0px|false|false" /]
      [et_pb_button button_url="/contact/?intent=volunteer" button_text="Volunteer with us" _builder_version="4.27.4" module_class="as-btn-ghost" custom_margin="||0px|1rem|false|false" /]
    [/et_pb_column]
    [et_pb_column type="1_2" _builder_version="4.27.4"]
      [et_pb_image src="https://www.autismsanctuary.org/wp-content/uploads/2026/08/edgefield.jpg" alt="Historic Edgefield farmhouse at Autism Sanctuary." _builder_version="4.27.4" module_class="as-native-image" border_radii="on|16px|16px|16px|16px" align="center" /]
    [/et_pb_column]
  [/et_pb_row]
[/et_pb_section]
SC;
$r3 = \ET\Builder\Packages\Conversion\Conversion::maybeConvertContent($sc3, true, 692, true);
echo "Section 3 (Our Home) converted: " . strlen($r3) . " bytes\n";

// 4. Convert Section 4: Looking Ahead
$sc4 = <<<SC
[et_pb_section fb_built="1" _builder_version="4.27.4" module_class="as-section as-looking-ahead-section" custom_padding="4.5rem|0px|4.5rem|0px|false|false" background_color="#f5f2e8"]
  [et_pb_row _builder_version="4.27.4" max_width="80rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false" column_structure="1_2,1_2" use_custom_gutter="on" gutter_width="2"]
    [et_pb_column type="1_2" _builder_version="4.27.4"]
      [et_pb_image src="https://www.autismsanctuary.org/wp-content/uploads/uploaded_images_videos/IMG_5762-scaled.jpg" alt="Farm and animal activities at Autism Sanctuary" _builder_version="4.27.4" module_class="as-native-image" border_radii="on|16px|16px|16px|16px" align="center" /]
    [/et_pb_column]
    [et_pb_column type="1_2" _builder_version="4.27.4"]
      [et_pb_text _builder_version="4.27.4" module_class="as-eyebrow" text_font="Source Sans 3||||||||" text_text_color="#C28B38" custom_margin="||0.5rem||false|false"]<p class="as-eyebrow">Looking ahead</p>[/et_pb_text]
      [et_pb_heading title="Growing capacity for purpose and belonging." _builder_version="4.27.4" title_font="Cormorant Garamond||||||||" title_text_color="#1E3D2C" title_font_size="2.25rem" custom_margin="||1rem||false|false" /]
      [et_pb_text _builder_version="4.27.4" module_class="as-lede" text_font="Source Sans 3||||||||" text_text_color="#4A534C" text_font_size="1.15rem" custom_margin="||1rem||false|false"]<p class="as-lede">With your support, we are advancing capital and program priorities that strengthen life at Autism Sanctuary.</p>[/et_pb_text]
      [et_pb_text _builder_version="4.27.4" text_font="Source Sans 3||||||||" text_text_color="#4A534C" custom_margin="||1.5rem||false|false"]
        <ul class="as-checklist">
          <li><strong>Activity Barn:</strong> a purpose-built program space that supports growth and moves day programming out of the historic house basement.</li>
          <li><strong>Facility upgrades:</strong> improvements to existing campus resources that keep outdoor and indoor days safe, welcoming, and ready for more people.</li>
          <li><strong>Workplace opportunities under development:</strong> expanding vocational pathways and Workplace Assistance so more people can build skills and meaningful work.</li>
        </ul>
      [/et_pb_text]
      [et_pb_button button_url="/donate/" button_text="Support this work" _builder_version="4.27.4" module_class="as-btn-primary" /]
    [/et_pb_column]
  [/et_pb_row]
[/et_pb_section]
SC;
$r4 = \ET\Builder\Packages\Conversion\Conversion::maybeConvertContent($sc4, true, 692, true);
echo "Section 4 (Looking Ahead) converted: " . strlen($r4) . " bytes\n";

// 5. Convert Section 6: Get In Touch
$sc6 = <<<SC
[et_pb_section fb_built="1" _builder_version="4.27.4" module_class="as-section as-section--forest" custom_padding="3.5rem|0px|4.5rem|0px|false|false" background_color="#1E3D2C"]
  [et_pb_row _builder_version="4.27.4" max_width="56rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false"]
    [et_pb_column type="4_4" _builder_version="4.27.4"]
      [et_pb_heading title="Get in touch" _builder_version="4.27.4" title_font="Cormorant Garamond||||||||" title_text_color="#ffffff" title_font_size="2.5rem" title_line_height="1.2" custom_margin="||1.5rem||false|false" /]
      [et_pb_button button_url="/contact/" button_text="Contact us" _builder_version="4.27.4" module_class="as-btn-primary" custom_margin="||0px|0px|false|false" /]
      [et_pb_button button_url="/programs/" button_text="See programs" _builder_version="4.27.4" module_class="as-btn-ghost" custom_margin="||0px|1rem|false|false" /]
    [/et_pb_column]
  [/et_pb_row]
[/et_pb_section]
SC;
$r6 = \ET\Builder\Packages\Conversion\Conversion::maybeConvertContent($sc6, true, 692, true);
echo "Section 6 (Get in Touch) converted: " . strlen($r6) . " bytes\n";

// 6. Extract Section 0 (Hero) & Section 5 (Newsroom) from live post 532
$parsed_532 = parse_blocks($live_home->post_content);
$sec0_hero = $parsed_532[0]['innerBlocks'][0];
$hero_markup = serialize_block($sec0_hero);
echo "Hero section extracted: " . strlen($hero_markup) . " bytes\n";

$sec5_news = $parsed_532[0]['innerBlocks'][5];
$sec5_markup = serialize_block($sec5_news);
echo "Newsroom section extracted: " . strlen($sec5_markup) . " bytes\n";

// 7. Assemble Full Page Content
$final_content = trim($hero_markup) . "\n\n" .
                 trim($r1) . "\n\n" .
                 trim($r2) . "\n\n" .
                 trim($r3) . "\n\n" .
                 trim($r4) . "\n\n" .
                 trim($sec5_markup) . "\n\n" .
                 trim($r6);

echo "Total final content length: " . strlen($final_content) . " bytes\n";

// Update Page 692 in Database
$wpdb->update($wpdb->posts, ['post_content' => $final_content], ['ID' => 692]);
clean_post_cache(692);

update_post_meta(692, '_et_pb_use_builder', 'on');
update_post_meta(692, '_et_pb_built_for_post_type', 'page');
update_post_meta(692, '_et_builder_version', '5.11.1');

// Flush caches
if (class_exists('\Hummingbird\WP_Hummingbird')) {
    \Hummingbird\WP_Hummingbird::flush_cache(true, true);
    echo "Hummingbird cache flushed.\n";
}
wp_cache_flush();
echo "WordPress object cache flushed.\n";

echo "=== SUCCESS: Page 692 (Home Revised) 100% Native Divi 5 conversion complete! ===\n";
