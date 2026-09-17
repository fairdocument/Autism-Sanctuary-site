<?php
/**
 * 1. Save native Divi 5 work as separate preview pages:
 *    - /home-revised/
 *    - /people-revised/
 *    - /contact-revised/
 * 2. Revert the live pages (/home/, /people/, /contact/) back to their exact pre-pilot backup state.
 *
 * Run: wp eval-file wordpress-migration/save-revised-and-revert.php
 */

if (!defined('ABSPATH')) {
    exit(1);
}

if (!class_exists('\\ET\\Builder\\Packages\\Conversion\\Conversion')) {
    echo "Divi 5 Conversion API not available.\n";
    return;
}

\ET\Builder\Packages\Conversion\Conversion::initialize_shortcode_framework();

echo "====================================================================\n";
echo "1. REVERTING LIVE PAGES (/home/, /people/, /contact/) FROM BACKUP\n";
echo "====================================================================\n";

// --- Revert Home (ID 532) ---
$home_backup = glob(WP_CONTENT_DIR . '/as-backup-pilot-home-*/home.raw.txt');
if (!empty($home_backup) && file_exists($home_backup[0])) {
    $raw_home = file_get_contents($home_backup[0]);
    wp_update_post([
        'ID'           => 532,
        'post_content' => wp_slash($raw_home),
    ]);
    update_post_meta(532, '_et_pb_use_builder', 'on');
    update_post_meta(532, '_et_pb_page_layout', 'et_no_sidebar');
    update_post_meta(532, '_et_pb_show_title', 'off');
    echo "✓ Reverted live /home/ (ID 532) from: " . basename(dirname($home_backup[0])) . "\n";
} else {
    echo "! Warning: Home backup file not found.\n";
}

// --- Revert People (ID 419) ---
$people_backup = glob(WP_CONTENT_DIR . '/as-backup-pilot-people-*/people.raw.txt');
if (!empty($people_backup) && file_exists($people_backup[0])) {
    $raw_people = file_get_contents($people_backup[0]);
    wp_update_post([
        'ID'           => 419,
        'post_content' => wp_slash($raw_people),
    ]);
    update_post_meta(419, '_et_pb_use_builder', 'on');
    update_post_meta(419, '_et_pb_page_layout', 'et_no_sidebar');
    update_post_meta(419, '_et_pb_show_title', 'off');
    echo "✓ Reverted live /people/ (ID 419) from: " . basename(dirname($people_backup[0])) . "\n";
} else {
    echo "! Warning: People backup file not found.\n";
}

// --- Revert Contact (ID 36) ---
$contact_backup = glob(WP_CONTENT_DIR . '/as-backup-pilot-contact-*/contact.raw.txt');
if (!empty($contact_backup) && file_exists($contact_backup[0])) {
    $raw_contact = file_get_contents($contact_backup[0]);
    wp_update_post([
        'ID'           => 36,
        'post_content' => wp_slash($raw_contact),
    ]);
    update_post_meta(36, '_et_pb_use_builder', 'on');
    update_post_meta(36, '_et_pb_page_layout', 'et_no_sidebar');
    update_post_meta(36, '_et_pb_show_title', 'off');
    echo "✓ Reverted live /contact/ (ID 36) from: " . basename(dirname($contact_backup[0])) . "\n";
} else {
    echo "! Warning: Contact backup file not found.\n";
}


echo "\n====================================================================\n";
echo "2. CREATING / UPDATING SEPARATE PREVIEW PAGES\n";
echo "====================================================================\n";

function get_or_create_page($slug, $title) {
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
    echo "Created new page '{$title}' (/{$slug}/) ID: {$id}\n";
    return $id;
}

// Helper to get avatar URL by slug
function as_avatar_url_preview($slug) {
    $att = get_posts([
        'post_type'   => 'attachment',
        'name'        => $slug,
        'post_status' => 'inherit',
        'numberposts' => 1,
    ]);
    return !empty($att) ? wp_get_attachment_url($att[0]->ID) : '';
}

// -------------------------------------------------------------------------
// A. /people-revised/
// -------------------------------------------------------------------------
$people_revised_id = get_or_create_page('people-revised', 'People (Revised)');

$avatars = [
    'jb' => as_avatar_url_preview('avatar-jason-brewster'),
    'rk' => as_avatar_url_preview('avatar-robert-kreps'),
    'mo' => as_avatar_url_preview('avatar-matthew-osborne'),
    'rn' => as_avatar_url_preview('avatar-rose-neville'),
    'ob' => as_avatar_url_preview('avatar-olivia-bruno'),
    'ik' => as_avatar_url_preview('avatar-isabelle-kueser'),
];

$sc_people = '';
$sc_people .= '[et_pb_section fb_built="1" _builder_version="4.27.4" module_class="as-banner" custom_padding="4rem|0px|2.5rem|0px|false|false" background_color="RGBA(255,255,255,0)"]';
$sc_people .= '[et_pb_row _builder_version="4.27.4" max_width="72rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false"]';
$sc_people .= '[et_pb_column type="4_4" _builder_version="4.27.4"]';
$sc_people .= '[et_pb_text _builder_version="4.27.4" module_class="as-eyebrow" text_font="Source Sans 3||||||||" text_text_color="#C28B38" custom_margin="||0.5rem||false|false"]<p class="as-eyebrow">People</p>[/et_pb_text]';
$sc_people .= '[et_pb_heading title="The team guiding Autism Sanctuary." _builder_version="4.27.4" title_font="Cormorant Garamond||||||||" title_text_color="#1E3D2C" title_font_size="2.75rem" custom_margin="||1rem||false|false" /]';
$sc_people .= '[et_pb_text _builder_version="4.27.4" module_class="as-lede" text_font="Source Sans 3||||||||" text_text_color="#4A534C" text_font_size="1.2rem" custom_margin="||0px||false|false"]<p class="as-lede">The volunteer board and staff leaders who guide Autism Sanctuary’s mission, programs, and daily operations.</p>[/et_pb_text]';
$sc_people .= '[/et_pb_column][/et_pb_row][/et_pb_section]';

$sc_people .= '[et_pb_section fb_built="1" _builder_version="4.27.4" module_class="as-section as-people-section" custom_padding="0px|0px|3rem|0px|false|false" background_color="RGBA(255,255,255,0)"]';
$sc_people .= '[et_pb_row _builder_version="4.27.4" max_width="44rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false"]';
$sc_people .= '[et_pb_column type="4_4" _builder_version="4.27.4"]';
$sc_people .= '[et_pb_heading title="Board of Directors" _builder_version="4.27.4" title_font="Cormorant Garamond||||||||" title_text_color="#1E3D2C" title_font_size="2rem" custom_margin="||1.5rem||false|false" /]';
$sc_people .= sprintf('[et_pb_team_member name="Jason Brewster" position="President / Founder" image_url="%s" module_class="as-person-member" _builder_version="4.27.4"]Parent of an autistic adult with high support needs and founder of Autism Sanctuary (2020). Entrepreneur and Director of Venture Programming at the University of Virginia’s Darden School of Business.[/et_pb_team_member]', esc_url($avatars['jb']));
$sc_people .= sprintf('[et_pb_team_member name="Robert Kreps" position="Treasurer" image_url="%s" module_class="as-person-member" _builder_version="4.27.4"]Parent of an autistic adult, retired engineer, and volunteer president of the Charlottesville Regional Autism Advocacy Group.[/et_pb_team_member]', esc_url($avatars['rk']));
$sc_people .= sprintf('[et_pb_team_member name="Matthew Osborne" position="Secretary" image_url="%s" module_class="as-person-member" _builder_version="4.27.4"]Director of Adult and Residential Services at the Faison Center. Psychologist and Licensed Board Certified Behavior Analyst.[/et_pb_team_member]', esc_url($avatars['mo']));
$sc_people .= sprintf('[et_pb_team_member name="Rose Neville" position="Board Member" image_url="%s" module_class="as-person-member" _builder_version="4.27.4"]Research Assistant Professor of Education and Director of the UVA Autism Research Core.[/et_pb_team_member]', esc_url($avatars['rn']));
$sc_people .= '[/et_pb_column][/et_pb_row][/et_pb_section]';

$sc_people .= '[et_pb_section fb_built="1" _builder_version="4.27.4" module_class="as-section as-people-section" custom_padding="0px|0px|4.5rem|0px|false|false" background_color="RGBA(255,255,255,0)"]';
$sc_people .= '[et_pb_row _builder_version="4.27.4" max_width="44rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false"]';
$sc_people .= '[et_pb_column type="4_4" _builder_version="4.27.4"]';
$sc_people .= '[et_pb_heading title="Management" _builder_version="4.27.4" title_font="Cormorant Garamond||||||||" title_text_color="#1E3D2C" title_font_size="2rem" custom_margin="||1.5rem||false|false" /]';
$sc_people .= sprintf('[et_pb_team_member name="Olivia Bruno" position="Executive Director" image_url="%s" module_class="as-person-member" _builder_version="4.27.4"]Strategic leader focused on strengthening services and supporting the developmental disabilities community.[/et_pb_team_member]', esc_url($avatars['ob']));
$sc_people .= sprintf('[et_pb_team_member name="Isabelle (Izzy) Kueser" position="Director of Adult Services" image_url="%s" module_class="as-person-member" _builder_version="4.27.4"]Leads direct care teams and creates meaningful person-centered experiences.[/et_pb_team_member]', esc_url($avatars['ik']));
$sc_people .= '[/et_pb_column][/et_pb_row][/et_pb_section]';

$conv_people = \ET\Builder\Packages\Conversion\Conversion::maybeConvertContent($sc_people, true, $people_revised_id, true);
wp_update_post([
    'ID'           => $people_revised_id,
    'post_content' => wp_slash($conv_people),
]);
update_post_meta($people_revised_id, '_et_pb_use_builder', 'on');
update_post_meta($people_revised_id, '_et_pb_page_layout', 'et_no_sidebar');
update_post_meta($people_revised_id, '_et_pb_show_title', 'off');
update_post_meta($people_revised_id, '_et_builder_version', '5.11.1');
echo "✓ Saved native Divi work to /people-revised/ (ID: {$people_revised_id})\n";


// -------------------------------------------------------------------------
// B. /contact-revised/
// -------------------------------------------------------------------------
$contact_revised_id = get_or_create_page('contact-revised', 'Contact (Revised)');

$sc_contact = '';
$sc_contact .= '[et_pb_section fb_built="1" _builder_version="4.27.4" module_class="as-banner" custom_padding="4rem|0px|2.5rem|0px|false|false" background_color="RGBA(255,255,255,0)"]';
$sc_contact .= '[et_pb_row _builder_version="4.27.4" max_width="72rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false"]';
$sc_contact .= '[et_pb_column type="4_4" _builder_version="4.27.4"]';
$sc_contact .= '[et_pb_text _builder_version="4.27.4" module_class="as-eyebrow" text_font="Source Sans 3||||||||" text_text_color="#C28B38" custom_margin="||0.5rem||false|false"]<p class="as-eyebrow">Contact</p>[/et_pb_text]';
$sc_contact .= '[et_pb_heading title="Get in touch with our team." _builder_version="4.27.4" title_font="Cormorant Garamond||||||||" title_text_color="#1E3D2C" title_font_size="2.75rem" custom_margin="||1rem||false|false" /]';
$sc_contact .= '[et_pb_text _builder_version="4.27.4" module_class="as-lede" text_font="Source Sans 3||||||||" text_text_color="#4A534C" text_font_size="1.2rem" custom_margin="||0px||false|false"]<p class="as-lede">Ask about programs, volunteering, jobs, donating, or general information.</p>[/et_pb_text]';
$sc_contact .= '[/et_pb_column][/et_pb_row][/et_pb_section]';

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

$form_html = <<<HTML
<div class="as-contact-form-wrap">
  <h2>Send an inquiry</h2>
  [gravityform id="1" title="false" description="false" ajax="true"]
</div>
HTML;

$sc_contact .= '[et_pb_section fb_built="1" _builder_version="4.27.4" module_class="as-section as-contact-section" custom_padding="0px|0px|4.5rem|0px|false|false" background_color="RGBA(255,255,255,0)"]';
$sc_contact .= '[et_pb_row _builder_version="4.27.4" max_width="72rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false" column_structure="1_3,2_3" use_custom_gutter="on" gutter_width="3"]';
$sc_contact .= '[et_pb_column type="1_3" _builder_version="4.27.4"][et_pb_text _builder_version="4.27.4" module_class="as-contact-sidebar" text_font="Source Sans 3||||||||" text_text_color="#4A534C"]' . $sidebar_html . '[/et_pb_text][/et_pb_column]';
$sc_contact .= '[et_pb_column type="2_3" _builder_version="4.27.4"][et_pb_text _builder_version="4.27.4" module_class="as-contact-form-module" text_font="Source Sans 3||||||||" text_text_color="#4A534C"]' . $form_html . '[/et_pb_text][/et_pb_column]';
$sc_contact .= '[/et_pb_row][/et_pb_section]';

$conv_contact = \ET\Builder\Packages\Conversion\Conversion::maybeConvertContent($sc_contact, true, $contact_revised_id, true);
wp_update_post([
    'ID'           => $contact_revised_id,
    'post_content' => wp_slash($conv_contact),
]);
update_post_meta($contact_revised_id, '_et_pb_use_builder', 'on');
update_post_meta($contact_revised_id, '_et_pb_page_layout', 'et_no_sidebar');
update_post_meta($contact_revised_id, '_et_pb_show_title', 'off');
update_post_meta($contact_revised_id, '_et_builder_version', '5.11.1');
echo "✓ Saved native Divi work to /contact-revised/ (ID: {$contact_revised_id})\n";


// -------------------------------------------------------------------------
// C. /home-revised/
// -------------------------------------------------------------------------
$home_revised_id = get_or_create_page('home-revised', 'Home (Revised)');

$img_cattle = 'https://www.autismsanctuary.org/wp-content/uploads/2026/09/IMG_2421-scaled.jpg';
$img_aerial = 'https://www.autismsanctuary.org/wp-content/uploads/2026/08/edgefield-aerial.jpg';
$img_looking = 'https://www.autismsanctuary.org/wp-content/uploads/2026/09/IMG_2230-scaled.jpeg';

$sc_home = '';

// Hero
$sc_home .= '[et_pb_section fb_built="1" _builder_version="4.27.4" module_class="as-hero" custom_padding="5rem|0px|4rem|0px|false|false" background_color="RGBA(255,255,255,0)"]';
$sc_home .= '[et_pb_row _builder_version="4.27.4" max_width="72rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false"]';
$sc_home .= '[et_pb_column type="4_4" _builder_version="4.27.4"]';
$sc_home .= '[et_pb_text _builder_version="4.27.4" module_class="as-eyebrow" text_font="Source Sans 3||||||||" text_text_color="#C28B38" custom_margin="||0.5rem||false|false"]<p class="as-eyebrow">501(c)(3) · Virginia DBHDS-licensed · Western Albemarle</p>[/et_pb_text]';
$sc_home .= '[et_pb_heading title="A working farm in the foothills of the Blue Ridge where people with developmental disabilities are supported and empowered to grow in nature." _builder_version="4.27.4" title_font="Cormorant Garamond||||||||" title_text_color="#1E3D2C" title_font_size="3rem" title_line_height="1.2" custom_margin="||1.5rem||false|false" /]';
$sc_home .= '[/et_pb_column][/et_pb_row]';
$sc_home .= '[et_pb_row _builder_version="4.27.4" max_width="72rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false" column_structure="1_4,1_4,1_2"]';
$sc_home .= '[et_pb_column type="1_4" _builder_version="4.27.4"][et_pb_button button_url="#approach" button_text="Our approach" _builder_version="4.27.4" module_class="as-btn-primary" /][/et_pb_column]';
$sc_home .= '[et_pb_column type="1_4" _builder_version="4.27.4"][et_pb_button button_url="#services" button_text="Licensed services" _builder_version="4.27.4" module_class="as-btn-ghost" /][/et_pb_column]';
$sc_home .= '[et_pb_column type="1_2" _builder_version="4.27.4"][/et_pb_column][/et_pb_row][/et_pb_section]';

// Approach
$sc_home .= '[et_pb_section fb_built="1" _builder_version="4.27.4" module_id="approach" module_class="as-section as-approach-section" custom_padding="4.5rem|0px|4.5rem|0px|false|false" background_color="RGBA(255,255,255,0)"]';
$sc_home .= '[et_pb_row _builder_version="4.27.4" max_width="80rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false" column_structure="1_2,1_2" use_custom_gutter="on" gutter_width="2"]';
$sc_home .= '[et_pb_column type="1_2" _builder_version="4.27.4"]';
$sc_home .= '[et_pb_text _builder_version="4.27.4" module_class="as-eyebrow" text_font="Source Sans 3||||||||" text_text_color="#C28B38" custom_margin="||0.5rem||false|false"]<p class="as-eyebrow">Our approach</p>[/et_pb_text]';
$sc_home .= '[et_pb_heading title="Purpose and belonging, rooted in nature." _builder_version="4.27.4" title_font="Cormorant Garamond||||||||" title_text_color="#1E3D2C" title_font_size="2.25rem" custom_margin="||1rem||false|false" /]';
$sc_home .= '[et_pb_text _builder_version="4.27.4" module_class="as-lede" text_font="Source Sans 3||||||||" text_text_color="#4A534C" text_font_size="1.15rem" custom_margin="||1.5rem||false|false"]<p class="as-lede">People in our program engage with trails, animals, gardens, and staff who care. Days are designed around a sense of purpose and belonging, with an emphasis on growth and meaningful relationships.</p>[/et_pb_text]';
$sc_home .= '[et_pb_button button_url="/about/" button_text="About Autism Sanctuary" _builder_version="4.27.4" module_class="as-btn-primary" /]';
$sc_home .= '[/et_pb_column]';
$sc_home .= '[et_pb_column type="1_2" _builder_version="4.27.4"]';
$sc_home .= sprintf('[et_pb_image src="%s" alt="Participant with cattle in pasture" _builder_version="4.27.4" border_radii="on|12px|12px|12px|12px" align="center" /]', esc_url($img_cattle));
$sc_home .= '[/et_pb_column][/et_pb_row][/et_pb_section]';

// Services
$sc_home .= '[et_pb_section fb_built="1" _builder_version="4.27.4" module_id="services" module_class="as-section as-services-section" custom_padding="4.5rem|0px|4.5rem|0px|false|false" background_color="RGBA(255,255,255,0)"]';
$sc_home .= '[et_pb_row _builder_version="4.27.4" max_width="80rem" custom_padding="0px|1.25rem|2rem|1.25rem|false|false" column_structure="2_3,1_3"]';
$sc_home .= '[et_pb_column type="2_3" _builder_version="4.27.4"]';
$sc_home .= '[et_pb_text _builder_version="4.27.4" module_class="as-eyebrow" text_font="Source Sans 3||||||||" text_text_color="#C28B38" custom_margin="||0.5rem||false|false"]<p class="as-eyebrow">Our services</p>[/et_pb_text]';
$sc_home .= '[et_pb_heading title="Licensed support rooted in connection, growth, and belonging." _builder_version="4.27.4" title_font="Cormorant Garamond||||||||" title_text_color="#1E3D2C" title_font_size="2.25rem" custom_margin="||1rem||false|false" /]';
$sc_home .= '[et_pb_text _builder_version="4.27.4" module_class="as-lede" text_font="Source Sans 3||||||||" text_text_color="#4A534C" text_font_size="1.15rem"]<p class="as-lede">Autism Sanctuary provides meaningful support through nature, community, and individualized services designed to help people thrive.</p>[/et_pb_text]';
$sc_home .= '[/et_pb_column]';
$sc_home .= '[et_pb_column type="1_3" _builder_version="4.27.4" custom_padding="2rem|0px|0px|0px|false|false"]';
$sc_home .= '[et_pb_button button_url="/programs/" button_text="Full service details" _builder_version="4.27.4" module_class="as-btn-primary" /]';
$sc_home .= '[/et_pb_column][/et_pb_row]';

$sc_home .= '[et_pb_row _builder_version="4.27.4" max_width="80rem" custom_padding="0px|1.25rem|1.5rem|1.25rem|false|false" column_structure="1_3,1_3,1_3" use_custom_gutter="on" gutter_width="2"]';
$sc_home .= '[et_pb_column type="1_3" _builder_version="4.27.4"][et_pb_blurb title="Group Day" _builder_version="4.27.4" module_class="as-native-service-card" use_icon="off"]<p>On-site weekday support on the farm—animal care, gardens, trails, and skill-building in nature.</p>[/et_pb_blurb][/et_pb_column]';
$sc_home .= '[et_pb_column type="1_3" _builder_version="4.27.4"][et_pb_blurb title="Community Coaching" _builder_version="4.27.4" module_class="as-native-service-card" use_icon="off"]<p>1:1 support in the community to build skills that open doors to everyday participation.</p>[/et_pb_blurb][/et_pb_column]';
$sc_home .= '[et_pb_column type="1_3" _builder_version="4.27.4"][et_pb_blurb title="Community Engagement" _builder_version="4.27.4" module_class="as-native-service-card" use_icon="off"]<p>1:3 small-group support for meaningful outings and community life beyond the farm.</p>[/et_pb_blurb][/et_pb_column]';
$sc_home .= '[/et_pb_row]';

$sc_home .= '[et_pb_row _builder_version="4.27.4" max_width="80rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false" column_structure="1_2,1_2" use_custom_gutter="on" gutter_width="2"]';
$sc_home .= '[et_pb_column type="1_2" _builder_version="4.27.4"][et_pb_blurb title="Residential & Home-Based" _builder_version="4.27.4" module_class="as-native-service-card" use_icon="off"]<p>Personalized supports in people’s homes and community settings where authorized.</p>[/et_pb_blurb][/et_pb_column]';
$sc_home .= '[et_pb_column type="1_2" _builder_version="4.27.4"][et_pb_blurb title="Workplace Assistance" _builder_version="4.27.4" module_class="as-native-service-card" use_icon="off"]<p>1:1 on-the-job support that helps people succeed in meaningful employment.</p>[/et_pb_blurb][/et_pb_column]';
$sc_home .= '[/et_pb_row][/et_pb_section]';

// Our Home
$sc_home .= '[et_pb_section fb_built="1" _builder_version="4.27.4" module_class="as-section as-home-farm-section" custom_padding="4.5rem|0px|4.5rem|0px|false|false" background_color="RGBA(255,255,255,0)"]';
$sc_home .= '[et_pb_row _builder_version="4.27.4" max_width="80rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false" column_structure="1_2,1_2" use_custom_gutter="on" gutter_width="2"]';
$sc_home .= '[et_pb_column type="1_2" _builder_version="4.27.4"]';
$sc_home .= '[et_pb_text _builder_version="4.27.4" module_class="as-eyebrow" text_font="Source Sans 3||||||||" text_text_color="#C28B38" custom_margin="||0.5rem||false|false"]<p class="as-eyebrow">Our home</p>[/et_pb_text]';
$sc_home .= '[et_pb_heading title="Edgefield in the Blue Ridge foothills." _builder_version="4.27.4" title_font="Cormorant Garamond||||||||" title_text_color="#1E3D2C" title_font_size="2.25rem" custom_margin="||1rem||false|false" /]';
$sc_home .= '[et_pb_text _builder_version="4.27.4" module_class="as-lede" text_font="Source Sans 3||||||||" text_text_color="#4A534C" text_font_size="1.15rem" custom_margin="||1rem||false|false"]<p class="as-lede">Autism Sanctuary operates from Edgefield—an extraordinary property and house stewarded by Frances Lee-Vandell. A 1780 home built by William Watkins was dismantled board by board and rebuilt onsite.</p>[/et_pb_text]';
$sc_home .= '[et_pb_text _builder_version="4.27.4" text_font="Source Sans 3||||||||" text_text_color="#4A534C" custom_margin="||1.5rem||false|false"]<p>Today, the property features a range of animals, a robust garden, walking trails, and a variety of activity stations. Thanks to Frances’ incredible generosity, this property and land have become the foundation of our programs and have changed the lives of many.</p>[/et_pb_text]';
$sc_home .= '[et_pb_button button_url="/our-farm/" button_text="Explore the property" _builder_version="4.27.4" module_class="as-btn-primary" custom_margin="||0px|0px|false|false" /]';
$sc_home .= '[et_pb_button button_url="/contact/?intent=volunteer" button_text="Volunteer with us" _builder_version="4.27.4" module_class="as-btn-ghost" custom_margin="||0px|1rem|false|false" /]';
$sc_home .= '[/et_pb_column]';
$sc_home .= '[et_pb_column type="1_2" _builder_version="4.27.4"]';
$sc_home .= sprintf('[et_pb_image src="%s" alt="Edgefield property aerial view" _builder_version="4.27.4" border_radii="on|12px|12px|12px|12px" align="center" /]', esc_url($img_aerial));
$sc_home .= '[/et_pb_column][/et_pb_row][/et_pb_section]';

// Looking Ahead
$sc_home .= '[et_pb_section fb_built="1" _builder_version="4.27.4" module_class="as-section as-looking-ahead-section" custom_padding="4.5rem|0px|4.5rem|0px|false|false" background_color="RGBA(255,255,255,0)"]';
$sc_home .= '[et_pb_row _builder_version="4.27.4" max_width="80rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false" column_structure="1_2,1_2" use_custom_gutter="on" gutter_width="2"]';
$sc_home .= '[et_pb_column type="1_2" _builder_version="4.27.4"]';
$sc_home .= sprintf('[et_pb_image src="%s" alt="Looking ahead at Autism Sanctuary" _builder_version="4.27.4" border_radii="on|12px|12px|12px|12px" align="center" /]', esc_url($img_looking));
$sc_home .= '[/et_pb_column]';
$sc_home .= '[et_pb_column type="1_2" _builder_version="4.27.4"]';
$sc_home .= '[et_pb_text _builder_version="4.27.4" module_class="as-eyebrow" text_font="Source Sans 3||||||||" text_text_color="#C28B38" custom_margin="||0.5rem||false|false"]<p class="as-eyebrow">Looking ahead</p>[/et_pb_text]';
$sc_home .= '[et_pb_heading title="Growing capacity for purpose and belonging." _builder_version="4.27.4" title_font="Cormorant Garamond||||||||" title_text_color="#1E3D2C" title_font_size="2.25rem" custom_margin="||1rem||false|false" /]';
$sc_home .= '[et_pb_text _builder_version="4.27.4" module_class="as-lede" text_font="Source Sans 3||||||||" text_text_color="#4A534C" text_font_size="1.15rem" custom_margin="||1rem||false|false"]<p class="as-lede">With your support, we are advancing capital and program priorities that strengthen life at Autism Sanctuary.</p>[/et_pb_text]';
$checklist_html = <<<HTML
<ul class="as-checklist">
  <li><strong>Activity Barn:</strong> a purpose-built program space that supports growth and moves day programming out of the historic house basement.</li>
  <li><strong>Facility upgrades:</strong> improvements to existing campus resources that keep outdoor and indoor days safe, welcoming, and ready for more people.</li>
  <li><strong>Workplace opportunities under development:</strong> expanding vocational pathways and Workplace Assistance so more people can build skills and meaningful work.</li>
</ul>
HTML;
$sc_home .= '[et_pb_text _builder_version="4.27.4" text_font="Source Sans 3||||||||" text_text_color="#4A534C" custom_margin="||1.5rem||false|false"]' . $checklist_html . '[/et_pb_text]';
$sc_home .= '[et_pb_button button_url="/donate/" button_text="Support this work" _builder_version="4.27.4" module_class="as-btn-primary" /]';
$sc_home .= '[/et_pb_column][/et_pb_row][/et_pb_section]';

// Newsroom
$sc_home .= '[et_pb_section fb_built="1" _builder_version="4.27.4" module_class="as-section as-home-news-section" custom_padding="4.5rem|0px|4.5rem|0px|false|false" background_color="RGBA(255,255,255,0)"]';
$sc_home .= '[et_pb_row _builder_version="4.27.4" max_width="80rem" custom_padding="0px|1.25rem|2rem|1.25rem|false|false" column_structure="2_3,1_3"]';
$sc_home .= '[et_pb_column type="2_3" _builder_version="4.27.4"]';
$sc_home .= '[et_pb_text _builder_version="4.27.4" module_class="as-eyebrow" text_font="Source Sans 3||||||||" text_text_color="#C28B38" custom_margin="||0.5rem||false|false"]<p class="as-eyebrow">Newsroom</p>[/et_pb_text]';
$sc_home .= '[et_pb_heading title="Latest updates" _builder_version="4.27.4" title_font="Cormorant Garamond||||||||" title_text_color="#1E3D2C" title_font_size="2.25rem" custom_margin="||0.5rem||false|false" /]';
$sc_home .= '[et_pb_text _builder_version="4.27.4" module_class="as-lede" text_font="Source Sans 3||||||||" text_text_color="#4A534C"]<p class="as-lede">Farm stories, press, and program notes from Autism Sanctuary.</p>[/et_pb_text]';
$sc_home .= '[/et_pb_column]';
$sc_home .= '[et_pb_column type="1_3" _builder_version="4.27.4" custom_padding="2rem|0px|0px|0px|false|false"]';
$sc_home .= '[et_pb_button button_url="/news/" button_text="Read more news stories" _builder_version="4.27.4" module_class="as-btn-ghost" /]';
$sc_home .= '[/et_pb_column][/et_pb_row]';
$sc_home .= '[et_pb_row _builder_version="4.27.4" max_width="80rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false"][et_pb_column type="4_4" _builder_version="4.27.4"]';
$sc_home .= '[et_pb_blog fullwidth="off" posts_number="3" include_categories="all" meta_date="F j, Y" show_thumbnail="on" show_content="off" show_more="off" show_author="off" show_date="on" show_categories="off" show_comments="off" show_excerpt="on" excerpt_length="28" show_pagination="off" use_overlay="off" _builder_version="4.27.4" module_class="as-home-news-blog" header_level="h3" header_font="Cormorant Garamond||||||||" header_font_size="1.4rem" header_text_color="#1E3D2C" body_font="Source Sans 3||||||||" body_text_color="#4A534C" meta_font="Source Sans 3||||||||" meta_text_color="#4A534C" meta_font_size="0.9rem" border_width_all="0px" /]';
$sc_home .= '[/et_pb_column][/et_pb_row][/et_pb_section]';

// CTA
$sc_home .= '[et_pb_section fb_built="1" _builder_version="4.27.4" module_class="as-section as-cta-section" custom_padding="4.5rem|0px|5rem|0px|false|false" background_color="RGBA(255,255,255,0)"]';
$sc_home .= '[et_pb_row _builder_version="4.27.4" max_width="44rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false"][et_pb_column type="4_4" _builder_version="4.27.4"]';
$sc_home .= '[et_pb_heading title="Get in touch" _builder_version="4.27.4" title_font="Cormorant Garamond||||||||" title_text_color="#1E3D2C" title_font_size="2.5rem" title_line_height="1.2" custom_margin="||1.5rem||false|false" /]';
$sc_home .= '[et_pb_button button_url="/contact/" button_text="Contact us" _builder_version="4.27.4" module_class="as-btn-primary" custom_margin="||0px|0px|false|false" /]';
$sc_home .= '[et_pb_button button_url="/programs/" button_text="See programs" _builder_version="4.27.4" module_class="as-btn-ghost" custom_margin="||0px|1rem|false|false" /]';
$sc_home .= '[/et_pb_column][/et_pb_row][/et_pb_section]';

$conv_home = \ET\Builder\Packages\Conversion\Conversion::maybeConvertContent($sc_home, true, $home_revised_id, true);
wp_update_post([
    'ID'           => $home_revised_id,
    'post_content' => wp_slash($conv_home),
]);
update_post_meta($home_revised_id, '_et_pb_use_builder', 'on');
update_post_meta($home_revised_id, '_et_pb_page_layout', 'et_no_sidebar');
update_post_meta($home_revised_id, '_et_pb_show_title', 'off');
update_post_meta($home_revised_id, '_et_builder_version', '5.11.1');
echo "✓ Saved native Divi work to /home-revised/ (ID: {$home_revised_id})\n";

// Flush caches
if (class_exists('\\Hummingbird\\WP_Hummingbird')) {
    \Hummingbird\WP_Hummingbird::flush_cache();
}
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
}
echo "\n=== All operations complete & caches flushed ===\n";
