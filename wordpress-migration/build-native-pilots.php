<?php
/**
 * Build Native Divi 5 Pilot Pages for People (ID 690) and Contact (ID 691).
 * Visually matches the live pages (/people/ and /contact/) while using
 * 100% native Divi 5 modules for Visual Builder editing.
 *
 * Run: wp eval-file wordpress-migration/build-native-pilots.php
 */

if (!defined('ABSPATH')) {
    exit(1);
}

if (!class_exists('\\ET\\Builder\\Packages\\Conversion\\Conversion')) {
    echo "Divi 5 Conversion API not available.\n";
    return;
}

\ET\Builder\Packages\Conversion\Conversion::initialize_shortcode_framework();

function get_or_create_page_by_slug($slug, $title) {
    $p = get_page_by_path($slug);
    if ($p) {
        return $p->ID;
    }
    $id = wp_insert_post([
        'post_title'   => $title,
        'post_name'    => $slug,
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_content' => '',
    ]);
    echo "Created page '{$title}' (/{$slug}/) ID: {$id}\n";
    return $id;
}

function get_avatar_url_by_slug($slug) {
    $att = get_posts([
        'post_type'   => 'attachment',
        'name'        => $slug,
        'post_status' => 'inherit',
        'numberposts' => 1,
    ]);
    if (!empty($att)) {
        return wp_get_attachment_url($att[0]->ID);
    }
    return 'https://www.autismsanctuary.org/wp-content/uploads/avatars/' . $slug . '.png';
}

echo "====================================================================\n";
echo "1. BUILDING /people-revised/ (Native Divi)\n";
echo "====================================================================\n";

$people_id = get_or_create_page_by_slug('people-revised', 'People (Revised)');

$avatars = [
    'jb' => get_avatar_url_by_slug('avatar-jason-brewster'),
    'rk' => get_avatar_url_by_slug('avatar-robert-kreps'),
    'mo' => get_avatar_url_by_slug('avatar-matthew-osborne'),
    'rn' => get_avatar_url_by_slug('avatar-rose-neville'),
    'ob' => get_avatar_url_by_slug('avatar-olivia-bruno'),
    'ik' => get_avatar_url_by_slug('avatar-isabelle-kueser'),
];

$sc_people = '';

// Green Hero Banner (Matching Live exactly: #2F5D43 background, white text)
$sc_people .= '[et_pb_section fb_built="1" _builder_version="4.27.4" module_class="native-banner" custom_padding="80px|0px|40px|0px|false|false" background_color="#2f5d43"]';
$sc_people .= '[et_pb_row _builder_version="4.27.4" max_width="80rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false"]';
$sc_people .= '[et_pb_column type="4_4" _builder_version="4.27.4"]';
$sc_people .= '[et_pb_text _builder_version="4.27.4" text_font="Source Sans 3||||||||" text_text_color="#ffffff" text_font_size="16px" custom_margin="||10px||false|false"]<p style="color:#ffffff;font-size:16px;margin:0 0 10px 0;">People</p>[/et_pb_text]';
$sc_people .= '[et_pb_heading title="Governance and leadership team" _builder_version="4.27.4" title_font="Cormorant Garamond|700|||||||" title_text_color="#ffffff" title_font_size="40px" custom_margin="||15px||false|false" /]';
$sc_people .= '[et_pb_text _builder_version="4.27.4" text_font="Source Sans 3||||||||" text_text_color="#ffffff" text_font_size="16px" custom_margin="||0px||false|false"]<p style="color:#ffffff;font-size:16px;margin:0;">The volunteer board and staff leaders who guide Autism Sanctuary’s mission, programs, and daily operations.</p>[/et_pb_text]';
$sc_people .= '[/et_pb_column][/et_pb_row][/et_pb_section]';

// Content Section (Cream background #f5f2e8 matching live)
$sc_people .= '[et_pb_section fb_built="1" _builder_version="4.27.4" module_class="native-people-section" custom_padding="50px|0px|70px|0px|false|false" background_color="#f5f2e8"]';
$sc_people .= '[et_pb_row _builder_version="4.27.4" max_width="44rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false"]';
$sc_people .= '[et_pb_column type="4_4" _builder_version="4.27.4"]';

// Board of Directors
$sc_people .= '[et_pb_heading title="Board of Directors" title_level="h2" _builder_version="4.27.4" title_font="Cormorant Garamond||||||||" title_text_color="#1E3D2C" title_font_size="32px" custom_margin="||0.5rem||false|false" /]';

$sc_people .= sprintf('[et_pb_team_member name="Jason Brewster" position="President / Founder" image_url="%s" module_class="native-person-member" _builder_version="4.27.4"]Parent of an autistic adult with high support needs and founder of Autism Sanctuary (2020). Entrepreneur and Director of Venture Programming at the University of Virginia’s Darden School of Business.[/et_pb_team_member]', esc_url($avatars['jb']));
$sc_people .= sprintf('[et_pb_team_member name="Robert Kreps" position="Treasurer" image_url="%s" module_class="native-person-member" _builder_version="4.27.4"]Parent of an autistic adult, retired engineer, and volunteer president of the Charlottesville Regional Autism Advocacy Group.[/et_pb_team_member]', esc_url($avatars['rk']));
$sc_people .= sprintf('[et_pb_team_member name="Matthew Osborne" position="Secretary" image_url="%s" module_class="native-person-member" _builder_version="4.27.4"]Director of Adult and Residential Services at the Faison Center. Psychologist and Licensed Board Certified Behavior Analyst.[/et_pb_team_member]', esc_url($avatars['mo']));
$sc_people .= sprintf('[et_pb_team_member name="Rose Neville" position="Board Member" image_url="%s" module_class="native-person-member" _builder_version="4.27.4"]Research Assistant Professor of Education and Director of the UVA Autism Research Core.[/et_pb_team_member]', esc_url($avatars['rn']));

// Management
$sc_people .= '[et_pb_heading title="Management" title_level="h2" _builder_version="4.27.4" title_font="Cormorant Garamond||||||||" title_text_color="#1E3D2C" title_font_size="32px" custom_margin="3rem||0.5rem||false|false" /]';

$sc_people .= sprintf('[et_pb_team_member name="Olivia Bruno" position="Executive Director" image_url="%s" module_class="native-person-member" _builder_version="4.27.4"]Strategic leader focused on strengthening services and supporting the developmental disabilities community.[/et_pb_team_member]', esc_url($avatars['ob']));
$sc_people .= sprintf('[et_pb_team_member name="Isabelle (Izzy) Kueser" position="Director of Adult Services" image_url="%s" module_class="native-person-member" _builder_version="4.27.4"]Leads direct care teams and creates meaningful person-centered experiences.[/et_pb_team_member]', esc_url($avatars['ik']));

$sc_people .= '[/et_pb_column][/et_pb_row][/et_pb_section]';

$conv_people = \ET\Builder\Packages\Conversion\Conversion::maybeConvertContent($sc_people, true, $people_id, true);
wp_update_post([
    'ID'           => $people_id,
    'post_content' => wp_slash($conv_people),
]);
update_post_meta($people_id, '_et_pb_use_builder', 'on');
update_post_meta($people_id, '_et_pb_page_layout', 'et_no_sidebar');
update_post_meta($people_id, '_et_pb_show_title', 'off');
update_post_meta($people_id, '_et_builder_version', '5.11.1');
echo "✓ Saved native Divi work to /people-revised/ (ID: {$people_id})\n";


echo "\n====================================================================\n";
echo "2. BUILDING /contact-revised/ (Native Divi)\n";
echo "====================================================================\n";

$contact_id = get_or_create_page_by_slug('contact-revised', 'Contact (Revised)');
$contact_img = 'https://www.autismsanctuary.org/wp-content/uploads/more_images_videos/02E9B18B-1C11-49F6-B757-A91663967B04.JPG';

$sc_contact = '';

// Green Hero Banner (Matching Live exactly: #2F5D43 background, white text)
$sc_contact .= '[et_pb_section fb_built="1" _builder_version="4.27.4" module_class="native-banner" custom_padding="80px|0px|40px|0px|false|false" background_color="#2f5d43"]';
$sc_contact .= '[et_pb_row _builder_version="4.27.4" max_width="80rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false"]';
$sc_contact .= '[et_pb_column type="4_4" _builder_version="4.27.4"]';
$sc_contact .= '[et_pb_text _builder_version="4.27.4" text_font="Source Sans 3||||||||" text_text_color="#ffffff" text_font_size="16px" custom_margin="||10px||false|false"]<p style="color:#ffffff;font-size:16px;margin:0 0 10px 0;">Contact</p>[/et_pb_text]';
$sc_contact .= '[et_pb_heading title="We are glad you reached out." _builder_version="4.27.4" title_font="Cormorant Garamond|700|||||||" title_text_color="#ffffff" title_font_size="40px" custom_margin="||15px||false|false" /]';
$sc_contact .= '[et_pb_text _builder_version="4.27.4" text_font="Source Sans 3||||||||" text_text_color="#ffffff" text_font_size="16px" custom_margin="||0px||false|false"]<p style="color:#ffffff;font-size:16px;margin:0;">Ask about programs, volunteering, jobs, donating, or general information.</p>[/et_pb_text]';
$sc_contact .= '[/et_pb_column][/et_pb_row][/et_pb_section]';

// Content Section (Cream background #f5f2e8 matching live)
$sc_contact .= '[et_pb_section fb_built="1" _builder_version="4.27.4" module_class="native-contact-section" custom_padding="50px|0px|80px|0px|false|false" background_color="#f5f2e8"]';
$sc_contact .= '[et_pb_row _builder_version="4.27.4" max_width="80rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false" column_structure="1_2,1_2"]';

// Left Column (1/2): Heading, Contact info, Photo
$sc_contact .= '[et_pb_column type="1_2" _builder_version="4.27.4"]';
$sc_contact .= '[et_pb_heading title="Send an inquiry" title_level="h2" _builder_version="4.27.4" title_font="Cormorant Garamond||||||||" title_text_color="#1E3D2C" title_font_size="36px" custom_margin="||1.5rem||false|false" /]';

$contact_info_html = <<<HTML
<div class="native-contact-info">
  <p><strong>Visit</strong><br />2860 Pea Ridge Road<br />Charlottesville, VA 22901</p>
  <p><strong>Call</strong><br /><a href="tel:+14342072118">(434) 207-2118</a></p>
  <p><strong>Email</strong><br /><a href="mailto:info@autismsanctuary.org">info@autismsanctuary.org</a></p>
  <p class="native-contact-social"><a href="https://www.instagram.com/autismsanctuary" target="_blank" rel="noopener">Instagram</a> · <a href="https://www.facebook.com/autismsanctuary" target="_blank" rel="noopener">Facebook</a></p>
</div>
HTML;

$sc_contact .= '[et_pb_text _builder_version="4.27.4" module_class="native-contact-details" text_font="Source Sans 3||||||||" text_text_color="#4A534C" custom_margin="||1.5rem||false|false"]' . $contact_info_html . '[/et_pb_text]';
$sc_contact .= sprintf('[et_pb_image src="%s" alt="Autism Sanctuary participants" _builder_version="4.27.4" border_radii="on|12px|12px|12px|12px" module_class="native-contact-image" /]', esc_url($contact_img));
$sc_contact .= '[/et_pb_column]';

// Right Column (1/2): Gravity Form (Gravity Forms renders the brand card styling automatically)
$sc_contact .= '[et_pb_column type="1_2" _builder_version="4.27.4"]';
$sc_contact .= '[et_pb_text _builder_version="4.27.4" module_class="native-contact-form" text_font="Source Sans 3||||||||" text_text_color="#4A534C"]<p>[gravityform id="1" title="false" description="false" ajax="true"]</p>[/et_pb_text]';
$sc_contact .= '[/et_pb_column]';

$sc_contact .= '[/et_pb_row][/et_pb_section]';

$conv_contact = \ET\Builder\Packages\Conversion\Conversion::maybeConvertContent($sc_contact, true, $contact_id, true);
wp_update_post([
    'ID'           => $contact_id,
    'post_content' => wp_slash($conv_contact),
]);
update_post_meta($contact_id, '_et_pb_use_builder', 'on');
update_post_meta($contact_id, '_et_pb_page_layout', 'et_no_sidebar');
update_post_meta($contact_id, '_et_pb_show_title', 'off');
update_post_meta($contact_id, '_et_builder_version', '5.11.1');
echo "✓ Saved native Divi work to /contact-revised/ (ID: {$contact_id})\n";

// Clear page-specific et-caches
$cache_base = WP_CONTENT_DIR . '/et-cache';
foreach ([$people_id, $contact_id] as $pid) {
    if (is_dir("{$cache_base}/{$pid}")) {
        array_map('unlink', glob("{$cache_base}/{$pid}/*.*"));
        rmdir("{$cache_base}/{$pid}");
        echo "Cleared et-cache for page ID {$pid}\n";
    }
}

// Flush caches
if (class_exists('\\Hummingbird\\WP_Hummingbird')) {
    \Hummingbird\WP_Hummingbird::flush_cache();
}
wp_cache_flush();
echo "\n=== Built both pilot pages and cleared caches ===\n";
