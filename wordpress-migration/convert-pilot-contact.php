<?php
/**
 * Pilot 2: Rebuild /contact/ with native Divi 5 row columns (1/3 + 2/3).
 * Run: wp eval-file wordpress-migration/convert-pilot-contact.php
 */

if (!defined('ABSPATH')) {
    exit(1);
}

if (!class_exists('\\ET\\Builder\\Packages\\Conversion\\Conversion')) {
    echo "Divi 5 Conversion API not available.\n";
    return;
}

$page = get_page_by_path('contact');
if (!$page) {
    echo "Page /contact/ not found.\n";
    return;
}

$backup_dir = WP_CONTENT_DIR . '/as-backup-pilot-contact-' . gmdate('Ymd-His');
wp_mkdir_p($backup_dir);
file_put_contents("{$backup_dir}/contact.raw.txt", $page->post_content);
echo "Backup created at: {$backup_dir}\n";

\ET\Builder\Packages\Conversion\Conversion::initialize_shortcode_framework();

// Construct Divi shortcode
$sc = '';

// 1. Banner Section
$sc .= '[et_pb_section fb_built="1" _builder_version="4.27.4" module_class="as-banner" custom_padding="4rem|0px|2.5rem|0px|false|false" background_color="RGBA(255,255,255,0)"]';
$sc .= '[et_pb_row _builder_version="4.27.4" max_width="72rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false"]';
$sc .= '[et_pb_column type="4_4" _builder_version="4.27.4"]';
$sc .= '[et_pb_text _builder_version="4.27.4" module_class="as-eyebrow" text_font="Source Sans 3||||||||" text_text_color="#C28B38" custom_margin="||0.5rem||false|false"]<p class="as-eyebrow">Contact</p>[/et_pb_text]';
$sc .= '[et_pb_heading title="Get in touch with our team." _builder_version="4.27.4" title_font="Cormorant Garamond||||||||" title_text_color="#1E3D2C" title_font_size="2.75rem" custom_margin="||1rem||false|false" /]';
$sc .= '[et_pb_text _builder_version="4.27.4" module_class="as-lede" text_font="Source Sans 3||||||||" text_text_color="#4A534C" text_font_size="1.2rem" custom_margin="||0px||false|false"]<p class="as-lede">Ask about programs, volunteering, jobs, donating, or general information.</p>[/et_pb_text]';
$sc .= '[/et_pb_column][/et_pb_row][/et_pb_section]';

// 2. Main 2-Column Split Section: Contact Info (1/3) + Form (2/3)
$sc .= '[et_pb_section fb_built="1" _builder_version="4.27.4" module_class="as-section as-contact-section" custom_padding="0px|0px|4.5rem|0px|false|false" background_color="RGBA(255,255,255,0)"]';
$sc .= '[et_pb_row _builder_version="4.27.4" max_width="72rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false" column_structure="1_3,2_3" use_custom_gutter="on" gutter_width="3"]';

// Column 1: Contact Details (1/3)
$sidebar_html = <<<HTML
<div class="as-contact-sidebar-card">
  <h3>Visit &amp; reach us</h3>
  <p><strong>Visit</strong><br />2860 Pea Ridge Road<br />Charlottesville, VA 22901</p>
  <p><strong>Call</strong><br /><a href="tel:+14342072118">(434) 207-2118</a></p>
  <p><strong>Email</strong><br /><a href="mailto:info@autismsanctuary.org">info@autismsanctuary.org</a></p>
  <p class="as-contact-social">
    <a href="https://www.instagram.com/autism.sanctuary/" rel="noopener noreferrer" target="_blank">Instagram</a> · 
    <a href="https://www.facebook.com/autismsanctuary" rel="noopener noreferrer" target="_blank">Facebook</a> · 
    <a href="https://www.linkedin.com/company/autismsanctuary" rel="noopener noreferrer" target="_blank">LinkedIn</a>
  </p>
</div>
HTML;

$sc .= '[et_pb_column type="1_3" _builder_version="4.27.4"]';
$sc .= '[et_pb_text _builder_version="4.27.4" module_class="as-contact-sidebar" text_font="Source Sans 3||||||||" text_text_color="#4A534C"]' . $sidebar_html . '[/et_pb_text]';
$sc .= '[/et_pb_column]';

// Column 2: Inquiry Form (2/3)
$form_html = <<<HTML
<div class="as-contact-form-wrap">
  <h2>Send an inquiry</h2>
  [gravityform id="1" title="false" description="false" ajax="true"]
</div>
HTML;

$sc .= '[et_pb_column type="2_3" _builder_version="4.27.4"]';
$sc .= '[et_pb_text _builder_version="4.27.4" module_class="as-contact-form-module" text_font="Source Sans 3||||||||" text_text_color="#4A534C"]' . $form_html . '[/et_pb_text]';
$sc .= '[/et_pb_column]';

$sc .= '[/et_pb_row][/et_pb_section]';

// Convert to Divi 5
$converted = \ET\Builder\Packages\Conversion\Conversion::maybeConvertContent(
    $sc,
    true,
    (int) $page->ID,
    true
);

if (!$converted || strpos($converted, '<!-- wp:divi/') === false) {
    echo "ERROR: Divi 5 conversion failed.\n";
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
echo "SUCCESS: Rebuilt /contact/ with native Divi 5 1/3 + 2/3 row structure!\n";
echo "Module inventory: " . json_encode($counts) . "\n";
