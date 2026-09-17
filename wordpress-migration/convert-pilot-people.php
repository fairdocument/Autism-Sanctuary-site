<?php
/**
 * Pilot 1: Rebuild /people/ with native Divi 5 team-member modules.
 * Run: wp eval-file wordpress-migration/convert-pilot-people.php
 */

if (!defined('ABSPATH')) {
    exit(1);
}

if (!class_exists('\\ET\\Builder\\Packages\\Conversion\\Conversion')) {
    echo "Divi 5 Conversion API not available.\n";
    return;
}

$page = get_page_by_path('people');
if (!$page) {
    echo "Page /people/ not found.\n";
    return;
}

$backup_dir = WP_CONTENT_DIR . '/as-backup-pilot-people-' . gmdate('Ymd-His');
wp_mkdir_p($backup_dir);
file_put_contents("{$backup_dir}/people.raw.txt", $page->post_content);
echo "Backup created at: {$backup_dir}\n";

// Helper to get avatar URL by slug
function as_get_avatar_url($slug) {
    $att = get_posts([
        'post_type'   => 'attachment',
        'name'        => $slug,
        'post_status' => 'inherit',
        'numberposts' => 1,
    ]);
    return !empty($att) ? wp_get_attachment_url($att[0]->ID) : '';
}

$avatars = [
    'jb' => as_get_avatar_url('avatar-jason-brewster'),
    'rk' => as_get_avatar_url('avatar-robert-kreps'),
    'mo' => as_get_avatar_url('avatar-matthew-osborne'),
    'rn' => as_get_avatar_url('avatar-rose-neville'),
    'ob' => as_get_avatar_url('avatar-olivia-bruno'),
    'ik' => as_get_avatar_url('avatar-isabelle-kueser'),
];

\ET\Builder\Packages\Conversion\Conversion::initialize_shortcode_framework();

// Construct Divi shortcodes for the page
$sc = '';

// 1. Banner Section
$sc .= '[et_pb_section fb_built="1" _builder_version="4.27.4" module_class="as-banner" custom_padding="4rem|0px|2.5rem|0px|false|false" background_color="RGBA(255,255,255,0)"]';
$sc .= '[et_pb_row _builder_version="4.27.4" max_width="72rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false"]';
$sc .= '[et_pb_column type="4_4" _builder_version="4.27.4"]';
$sc .= '[et_pb_text _builder_version="4.27.4" module_class="as-eyebrow" text_font="Source Sans 3||||||||" text_text_color="#C28B38" custom_margin="||0.5rem||false|false"]<p class="as-eyebrow">People</p>[/et_pb_text]';
$sc .= '[et_pb_heading title="The team guiding Autism Sanctuary." _builder_version="4.27.4" title_font="Cormorant Garamond||||||||" title_text_color="#1E3D2C" title_font_size="2.75rem" custom_margin="||1rem||false|false" /]';
$sc .= '[et_pb_text _builder_version="4.27.4" module_class="as-lede" text_font="Source Sans 3||||||||" text_text_color="#4A534C" text_font_size="1.2rem" custom_margin="||0px||false|false"]<p class="as-lede">The volunteer board and staff leaders who guide Autism Sanctuary’s mission, programs, and daily operations.</p>[/et_pb_text]';
$sc .= '[/et_pb_column][/et_pb_row][/et_pb_section]';

// 2. Board of Directors Section
$sc .= '[et_pb_section fb_built="1" _builder_version="4.27.4" module_class="as-section as-people-section" custom_padding="0px|0px|3rem|0px|false|false" background_color="RGBA(255,255,255,0)"]';
$sc .= '[et_pb_row _builder_version="4.27.4" max_width="44rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false"]';
$sc .= '[et_pb_column type="4_4" _builder_version="4.27.4"]';
$sc .= '[et_pb_heading title="Board of Directors" _builder_version="4.27.4" title_font="Cormorant Garamond||||||||" title_text_color="#1E3D2C" title_font_size="2rem" custom_margin="||1.5rem||false|false" /]';

// Jason Brewster
$sc .= sprintf(
    '[et_pb_team_member name="Jason Brewster" position="President / Founder" image_url="%s" module_class="as-person-member" _builder_version="4.27.4"]Parent of an autistic adult with high support needs and founder of Autism Sanctuary (2020). Entrepreneur and Director of Venture Programming at the University of Virginia’s Darden School of Business.[/et_pb_team_member]',
    esc_url($avatars['jb'])
);

// Robert Kreps
$sc .= sprintf(
    '[et_pb_team_member name="Robert Kreps" position="Treasurer" image_url="%s" module_class="as-person-member" _builder_version="4.27.4"]Parent of an autistic adult, retired engineer, and volunteer president of the Charlottesville Regional Autism Advocacy Group.[/et_pb_team_member]',
    esc_url($avatars['rk'])
);

// Matthew Osborne
$sc .= sprintf(
    '[et_pb_team_member name="Matthew Osborne" position="Secretary" image_url="%s" module_class="as-person-member" _builder_version="4.27.4"]Director of Adult and Residential Services at the Faison Center. Psychologist and Licensed Board Certified Behavior Analyst.[/et_pb_team_member]',
    esc_url($avatars['mo'])
);

// Rose Neville
$sc .= sprintf(
    '[et_pb_team_member name="Rose Neville" position="Board Member" image_url="%s" module_class="as-person-member" _builder_version="4.27.4"]Research Assistant Professor of Education and Director of the UVA Autism Research Core.[/et_pb_team_member]',
    esc_url($avatars['rn'])
);

$sc .= '[/et_pb_column][/et_pb_row][/et_pb_section]';

// 3. Management Section
$sc .= '[et_pb_section fb_built="1" _builder_version="4.27.4" module_class="as-section as-people-section" custom_padding="0px|0px|4.5rem|0px|false|false" background_color="RGBA(255,255,255,0)"]';
$sc .= '[et_pb_row _builder_version="4.27.4" max_width="44rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false"]';
$sc .= '[et_pb_column type="4_4" _builder_version="4.27.4"]';
$sc .= '[et_pb_heading title="Management" _builder_version="4.27.4" title_font="Cormorant Garamond||||||||" title_text_color="#1E3D2C" title_font_size="2rem" custom_margin="||1.5rem||false|false" /]';

// Olivia Bruno
$sc .= sprintf(
    '[et_pb_team_member name="Olivia Bruno" position="Executive Director" image_url="%s" module_class="as-person-member" _builder_version="4.27.4"]Strategic leader focused on strengthening services and supporting the developmental disabilities community.[/et_pb_team_member]',
    esc_url($avatars['ob'])
);

// Isabelle Kueser
$sc .= sprintf(
    '[et_pb_team_member name="Isabelle (Izzy) Kueser" position="Director of Adult Services" image_url="%s" module_class="as-person-member" _builder_version="4.27.4"]Leads direct care teams and creates meaningful person-centered experiences.[/et_pb_team_member]',
    esc_url($avatars['ik'])
);

$sc .= '[/et_pb_column][/et_pb_row][/et_pb_section]';

// Convert to Divi 5 block markup
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
echo "SUCCESS: Rebuilt /people/ with native Divi 5 modules!\n";
echo "Module inventory: " . json_encode($counts) . "\n";
