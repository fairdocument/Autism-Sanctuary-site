<?php
/**
 * Autism Sanctuary → WordPress (autismsanctuary2) migration.
 * Run: wp eval-file wordpress-migration/migrate.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

echo "=== Autism Sanctuary WP migration ===\n";

$MEDIA_SRC = '/home/sites/autismsanctuary-new/public_html/public/media';
$BRANDING_SRC = '/home/sites/autismsanctuary-new/public_html/public/branding';
$CSS_PATH = __DIR__ . '/custom.css';

function as_log($msg) {
	echo $msg . "\n";
}

function as_upsert_page($slug, $title, $content, $excerpt = '') {
	$existing = get_page_by_path($slug);
	$args = [
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_content' => $content,
		'post_excerpt' => $excerpt,
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'post_author'  => 1,
	];
	if ($existing) {
		$args['ID'] = $existing->ID;
		$id = wp_update_post($args, true);
	} else {
		$id = wp_insert_post($args, true);
	}
	if (is_wp_error($id)) {
		as_log("ERROR page {$slug}: " . $id->get_error_message());
		return 0;
	}
	update_post_meta($id, '_et_pb_use_builder', 'off');
	update_post_meta($id, '_et_pb_page_layout', 'et_no_sidebar');
	update_post_meta($id, '_et_pb_show_title', 'off');
	as_log("Page OK: /{$slug} (#{$id})");
	return (int) $id;
}

function as_media_url_map() {
	static $map = null;
	if ($map !== null) {
		return $map;
	}
	$map = [];
	$q = new WP_Query([
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => 100,
		'fields'         => 'ids',
	]);
	foreach ($q->posts as $id) {
		$file = basename(get_attached_file($id));
		$map[$file] = wp_get_attachment_url($id);
		$map['id:' . $file] = $id;
	}
	return $map;
}

function as_img($filename, $alt = '') {
	$map = as_media_url_map();
	$url = $map[$filename] ?? '';
	if (!$url) {
		return '';
	}
	$alt = esc_attr($alt);
	return '<img src="' . esc_url($url) . '" alt="' . $alt . '" loading="lazy" />';
}

function as_media($filename) {
	$map = as_media_url_map();
	return $map[$filename] ?? '';
}

function as_replace_media_paths($html) {
	return preg_replace_callback(
		'#(?:src|href)="(/media/([^"]+))"#',
		function ($m) {
			$url = as_media($m[2]);
			if (!$url) {
				return $m[0];
			}
			return str_replace($m[1], $url, $m[0]);
		},
		$html
	);
}

function as_btn($label, $href, $style = 'primary') {
	$class = $style === 'ghost' ? 'as-btn as-btn--ghost' : 'as-btn as-btn--primary';
	return '<a class="' . $class . '" href="' . esc_url($href) . '">' . esc_html($label) . '</a>';
}

function as_actions($buttons) {
	$html = '<div class="as-actions">';
	foreach ($buttons as $b) {
		$html .= as_btn($b[0], $b[1], $b[2] ?? 'primary');
	}
	$html .= '</div>';
	return $html;
}

function as_banner($eyebrow, $title, $lede = '') {
	$html = '<section class="as-banner"><div class="as-page">';
	$html .= '<p class="as-eyebrow">' . esc_html($eyebrow) . '</p>';
	$html .= '<h1>' . esc_html($title) . '</h1>';
	if ($lede) {
		$html .= '<p class="as-lede">' . esc_html($lede) . '</p>';
	}
	$html .= '</div></section>';
	return $html;
}

function as_prose_wrap($inner) {
	return '<div class="as-page as-prose">' . $inner . '</div>';
}

// ---------------------------------------------------------------------------
// 1. Site settings
// ---------------------------------------------------------------------------
update_option('blogname', 'Autism Sanctuary');
update_option('blogdescription', "Nature's haven for autism: where growth knows no limits.");
update_option('timezone_string', 'America/New_York');
update_option('permalink_structure', '/%postname%/');
flush_rewrite_rules();
as_log('Site settings updated');

// ---------------------------------------------------------------------------
// 2. Media
// ---------------------------------------------------------------------------
$files = [
	'autism-sanctuary-logo.png',
	'edgefield-aerial.jpg',
	'edgefield-house.jpg',
	'sanctuary-gardens.jpg',
	'hero-enchanted-forest.jpg',
	'farm-animals.jpg',
	'farm-activity.jpg',
	'farm-detail.jpg',
	'farm-portrait.jpg',
	'farm-startup.jpg',
	'event-awards.jpg',
	'hero-farm.mp4',
];

$existing_map = as_media_url_map();
foreach ($files as $file) {
	if (!empty($existing_map[$file])) {
		as_log("Media exists: {$file}");
		continue;
	}
	$path = $MEDIA_SRC . '/' . $file;
	if (!file_exists($path) && $file === 'autism-sanctuary-logo.png') {
		$path = $BRANDING_SRC . '/' . $file;
	}
	if (!file_exists($path)) {
		as_log("MISSING media: {$path}");
		continue;
	}
	$id = as_sideload_file($path, $file);
	as_log($id ? "Imported media #{$id}: {$file}" : "FAILED media: {$file}");
}

function as_sideload_file($path, $filename) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$tmp = wp_tempnam($filename);
	if (!$tmp || !copy($path, $tmp)) {
		return 0;
	}
	$file_array = [
		'name'     => $filename,
		'tmp_name' => $tmp,
	];
	$id = media_handle_sideload($file_array, 0, null, [
		'post_title' => pathinfo($filename, PATHINFO_FILENAME),
	]);
	if (is_wp_error($id)) {
		@unlink($tmp);
		as_log('sideload error: ' . $id->get_error_message());
		return 0;
	}
	return (int) $id;
}

// Reset static cache after imports
$ref = new ReflectionFunction('as_media_url_map');
// Force refresh by querying again via temporary override:
as_media_url_map_refresh();

function as_media_url_map_refresh() {
	// Re-call after clearing by using a global
	$GLOBALS['as_media_map_force'] = [];
	$q = new WP_Query([
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => 200,
		'fields'         => 'ids',
	]);
	foreach ($q->posts as $id) {
		$file = basename(get_attached_file($id));
		$GLOBALS['as_media_map_force'][$file] = wp_get_attachment_url($id);
		$GLOBALS['as_media_map_force']['id:' . $file] = $id;
	}
}

// Patch as_media to prefer refreshed map
function as_media_fresh($filename) {
	if (!empty($GLOBALS['as_media_map_force'][$filename])) {
		return $GLOBALS['as_media_map_force'][$filename];
	}
	return as_media($filename);
}

$logo_id = $GLOBALS['as_media_map_force']['id:autism-sanctuary-logo.png'] ?? 0;
if ($logo_id) {
	set_theme_mod('custom_logo', (int) $logo_id);
	$et = get_option('et_divi', []);
	if (!is_array($et)) {
		$et = [];
	}
	$et['divi_logo'] = as_media_fresh('autism-sanctuary-logo.png');
	$et['accent_color'] = '#2F5D43';
	$et['secondary_nav_bg'] = '#1E3D2C';
	$et['font_color'] = '#1B1B1B';
	$et['header_color'] = '#F7F4EC';
	$et['footer_bg'] = '#1E3D2C';
	$et['link_color'] = '#2F5D43';
	$et['body_font'] = 'Source Sans 3';
	$et['heading_font'] = 'Cormorant Garamond';
	update_option('et_divi', $et);
	as_log("Logo set (#{$logo_id})");
}

// ---------------------------------------------------------------------------
// 3. Custom CSS
// ---------------------------------------------------------------------------
if (file_exists($CSS_PATH)) {
	$css = file_get_contents($CSS_PATH);
	wp_update_custom_css_post($css);
	$et = get_option('et_divi', []);
	if (!is_array($et)) {
		$et = [];
	}
	$et['divi_custom_css'] = $css;
	update_option('et_divi', $et);
	as_log('Custom CSS applied');
}

// ---------------------------------------------------------------------------
// 4. Gravity Forms
// ---------------------------------------------------------------------------
$inquiry_id = as_ensure_inquiry_form();
$donate_id = as_ensure_donate_form();

function as_find_form_by_title($title) {
	if (!class_exists('GFAPI')) {
		return 0;
	}
	$forms = GFAPI::get_forms();
	foreach ($forms as $f) {
		if (isset($f['title']) && $f['title'] === $title) {
			return (int) $f['id'];
		}
	}
	return 0;
}

function as_ensure_inquiry_form() {
	if (!class_exists('GFAPI')) {
		as_log('GFAPI missing — skip inquiry form');
		return 0;
	}
	$existing = as_find_form_by_title('Inquiry');
	$form = [
		'title'        => 'Inquiry',
		'description'  => '',
		'labelPlacement' => 'top_label',
		'button'       => ['type' => 'text', 'text' => 'Send inquiry'],
		'fields'       => [
			[
				'type'         => 'text',
				'id'           => 1,
				'label'        => 'Name',
				'isRequired'   => true,
			],
			[
				'type'         => 'email',
				'id'           => 2,
				'label'        => 'Email',
				'isRequired'   => true,
			],
			[
				'type'         => 'phone',
				'id'           => 3,
				'label'        => 'Phone',
				'isRequired'   => false,
				'phoneFormat'  => 'standard',
			],
			[
				'type'               => 'select',
				'id'                 => 4,
				'label'              => 'I am contacting about',
				'isRequired'         => true,
				'allowsPrepopulate'  => true,
				'inputName'          => 'intent',
				'choices'            => [
					['text' => 'General information', 'value' => 'general'],
					['text' => 'Donating', 'value' => 'donate'],
					['text' => 'Volunteering', 'value' => 'volunteer'],
					['text' => 'Exploring a program fit', 'value' => 'program-fit'],
					['text' => 'Job opportunities', 'value' => 'jobs'],
				],
			],
			[
				'type'       => 'textarea',
				'id'         => 5,
				'label'      => 'Message',
				'isRequired' => true,
			],
		],
		'enableHoneypot' => true,
		'confirmations' => [
			[
				'id'       => uniqid(),
				'name'     => 'Default Confirmation',
				'isDefault'=> true,
				'type'     => 'message',
				'message'  => 'Thank you. We received your inquiry and will respond soon.',
			],
		],
		'notifications' => [
			[
				'id'      => uniqid(),
				'name'    => 'Admin Notification',
				'event'   => 'form_submission',
				'to'      => 'info@autismsanctuary.org',
				'toType'  => 'email',
				'subject' => 'Autism Sanctuary inquiry: {Name:1}',
				'message' => '{all_fields}',
				'from'    => '{admin_email}',
				'fromName'=> 'Autism Sanctuary Website',
				'replyTo' => '{Email:2}',
				'isActive'=> true,
			],
		],
	];

	if ($existing) {
		$form['id'] = $existing;
		$result = GFAPI::update_form($form);
		as_log($result === true ? "Inquiry form updated (#{$existing})" : 'Inquiry update failed');
		return $existing;
	}
	$id = GFAPI::add_form($form);
	if (is_wp_error($id)) {
		as_log('Inquiry form error: ' . $id->get_error_message());
		return 0;
	}
	as_log("Inquiry form created (#{$id})");
	return (int) $id;
}

function as_ensure_donate_form() {
	if (!class_exists('GFAPI')) {
		return 0;
	}
	$existing = as_find_form_by_title('Donate Inquiry');
	$form = [
		'title'   => 'Donate Inquiry',
		'button'  => ['type' => 'text', 'text' => 'Send gift inquiry'],
		'fields'  => [
			['type' => 'text', 'id' => 1, 'label' => 'Name', 'isRequired' => true],
			['type' => 'email', 'id' => 2, 'label' => 'Email', 'isRequired' => true],
			['type' => 'phone', 'id' => 3, 'label' => 'Phone', 'isRequired' => false],
			[
				'type' => 'select',
				'id' => 4,
				'label' => 'Gift preference',
				'isRequired' => true,
				'choices' => [
					['text' => 'Where needed most', 'value' => 'where-needed'],
					['text' => 'Day program', 'value' => 'day-program'],
					['text' => 'Residential', 'value' => 'residential'],
					['text' => 'Agriculture and land', 'value' => 'agriculture'],
				],
			],
			[
				'type' => 'select',
				'id' => 5,
				'label' => 'Approximate amount',
				'isRequired' => false,
				'choices' => [
					['text' => '$25', 'value' => '25'],
					['text' => '$50', 'value' => '50'],
					['text' => '$100', 'value' => '100'],
					['text' => '$250', 'value' => '250'],
					['text' => 'Other / discuss', 'value' => 'other'],
				],
			],
			[
				'type' => 'select',
				'id' => 6,
				'label' => 'Frequency',
				'choices' => [
					['text' => 'One-time', 'value' => 'one-time'],
					['text' => 'Monthly', 'value' => 'monthly'],
				],
			],
			['type' => 'text', 'id' => 7, 'label' => 'In honor / memory of (optional)', 'isRequired' => false],
			['type' => 'textarea', 'id' => 8, 'label' => 'Message', 'isRequired' => false],
		],
		'confirmations' => [
			[
				'id' => uniqid(),
				'name' => 'Default Confirmation',
				'isDefault' => true,
				'type' => 'page',
				'pageId' => 0, // filled after thanks page
				'message' => 'Thank you for your generosity. We will follow up with secure giving instructions shortly.',
			],
		],
		'notifications' => [
			[
				'id' => uniqid(),
				'name' => 'Admin Notification',
				'event' => 'form_submission',
				'to' => 'info@autismsanctuary.org',
				'toType' => 'email',
				'subject' => 'Donation inquiry from {Name:1}',
				'message' => '{all_fields}',
				'from' => '{admin_email}',
				'fromName' => 'Autism Sanctuary Website',
				'replyTo' => '{Email:2}',
				'isActive' => true,
			],
		],
	];

	if ($existing) {
		$form['id'] = $existing;
		GFAPI::update_form($form);
		as_log("Donate form updated (#{$existing})");
		return $existing;
	}
	$id = GFAPI::add_form($form);
	if (is_wp_error($id)) {
		as_log('Donate form error: ' . $id->get_error_message());
		return 0;
	}
	as_log("Donate form created (#{$id})");
	return (int) $id;
}

$gf_inquiry = $inquiry_id ? '[gravityform id="' . $inquiry_id . '" title="false" description="false" ajax="true"]' : '<p><a href="mailto:info@autismsanctuary.org">Email info@autismsanctuary.org</a></p>';
$gf_donate = $donate_id ? '[gravityform id="' . $donate_id . '" title="false" description="false" ajax="true"]' : '';

// ---------------------------------------------------------------------------
// 5. Pages
// ---------------------------------------------------------------------------
$aerial = as_media_fresh('edgefield-aerial.jpg');
$house = as_media_fresh('edgefield-house.jpg');
$gardens = as_media_fresh('sanctuary-gardens.jpg');
$forest = as_media_fresh('hero-enchanted-forest.jpg');
$animals = as_media_fresh('farm-animals.jpg');
$video = as_media_fresh('hero-farm.mp4');

$home_html = '
<section class="as-hero">
  <div class="as-hero__media">
    ' . ($video ? '<video autoplay muted loop playsinline poster="' . esc_url($aerial) . '"><source src="' . esc_url($video) . '" type="video/mp4"></video>' : '') . '
    ' . (!$video && $aerial ? '<img class="as-hero__poster" src="' . esc_url($aerial) . '" alt="Aerial view of Edgefield farm">' : '') . '
  </div>
  <div class="as-hero__shade"></div>
  <div class="as-hero__inner">
    <p class="as-eyebrow">501(c)(3) · Virginia DBHDS-licensed · Western Albemarle</p>
    <p class="as-brand">Autism Sanctuary</p>
    <h1>Meaningful days outdoors—with trails, animals, and a community that shows up.</h1>
    <p class="as-lede">A rural working farm on Pea Ridge Road where people with significant support needs spend real time in nature—alongside licensed services grounded in evidence-based and promising practices.</p>
    ' . as_actions([['See programs', '/programs'], ['Visit our farm story', '/our-farm', 'ghost']]) . '
  </div>
</section>

<section class="as-section as-section--cream">
  <div class="as-section__inner as-split">
    <div>
      <p class="as-eyebrow">Our model</p>
      <h2>One sanctuary today—deep roots in Western Albemarle.</h2>
      <p class="as-lede">We operate a single licensed location where people spend real time with trails, animals, gardens, and people who care. Days are designed for joy, regulation, and connection—not a clinical waiting room.</p>
      <ul class="as-checklist">
        <li><strong>High support needs welcome</strong> most people we serve rely on consistent, skilled staffing.</li>
        <li><strong>Evidence + practice here</strong> research-backed methods where they fit, and promising practices refined on this farm.</li>
        <li><strong>Local first</strong> Crozet, White Hall, and the wider Albemarle community help make the farm feel like more than a program.</li>
      </ul>
      ' . as_actions([['About Autism Sanctuary', '/about'], ['Ask about a fit', '/contact/?intent=program-fit', 'ghost']]) . '
    </div>
    <div class="as-split__media">' . ($gardens ? '<img src="' . esc_url($gardens) . '" alt="Garden paths and plantings at Autism Sanctuary’s Virginia farm.">' : '') . '</div>
  </div>
</section>

<section class="as-section as-section--meadow">
  <div class="as-section__inner">
    <p class="as-eyebrow">Programs</p>
    <h2>Licensed supports that still feel like a day on the farm.</h2>
    <div class="as-grid as-grid--3">
      <div class="as-feature"><h3>Group Day &amp; Community Coaching</h3><p>Weekday person-centered support in a natural learning environment—therapeutic horticulture, animal care, and guided community participation.</p></div>
      <div class="as-feature"><h3>Residential &amp; Home-Based</h3><p>Individualized residential pathways where authorized—designed for stability, dignity, and collaborative planning with families and guardians.</p></div>
      <div class="as-feature"><h3>Workplace Assistance</h3><p>Coaching that connects pre-vocational routines on the farm to community roles when that matches a person’s goals.</p></div>
    </div>
    ' . as_actions([['Full program details', '/programs']]) . '
  </div>
</section>

<section class="as-section">
  <div class="as-section__inner as-split as-split--reverse">
    <div>
      <p class="as-eyebrow">Our farm</p>
      <h2>Edgefield, trails, animals, and the Enchanted Forest.</h2>
      <p class="as-lede">From cattle and chickens to high-tunnel gardens and two walking trails, the land is the program. Neighbors and volunteers help keep trails open and days generous.</p>
      ' . as_actions([['Explore the property', '/our-farm'], ['Volunteer with us', '/contact/?intent=volunteer', 'ghost']]) . '
    </div>
    <div class="as-split__media">' . ($house ? '<img src="' . esc_url($house) . '" alt="Historic Edgefield farmhouse at Autism Sanctuary.">' : '') . '</div>
  </div>
</section>

<section class="as-section as-section--cream">
  <div class="as-section__inner as-split">
    <div>
      <p class="as-eyebrow">Support the sanctuary</p>
      <h2>Your gift keeps farm days joyful and safe.</h2>
      <p class="as-lede">Philanthropy fills gaps Medicaid and grants can miss: trails, barns, gardens, sensory play, and staff development for people with significant support needs.</p>
      ' . as_actions([['Donate', '/donate'], ['Ask about giving', '/contact/?intent=donate', 'ghost']]) . '
    </div>
    <div class="as-split__media">' . ($forest ? '<img src="' . esc_url($forest) . '" alt="Forest canopy along the Enchanted Forest trail.">' : '') . '</div>
  </div>
</section>

<section class="as-section as-section--forest">
  <div class="as-section__inner">
    <h2>Ready to talk?</h2>
    <p class="as-lede">Whether you are exploring programs, volunteering, careers, or a gift—we are glad you reached out.</p>
    ' . as_actions([['Send an inquiry', '/contact'], ['Admissions', '/admissions', 'ghost']]) . '
  </div>
</section>
';

$pages = [];

$pages['home'] = as_upsert_page(
	'home',
	'Care Farm in Western Albemarle',
	$home_html,
	'Autism Sanctuary is a Virginia DBHDS-licensed working farm in Western Albemarle.'
);

$about_body = as_replace_media_paths(
	file_get_contents(__DIR__ . '/content/about.html')
);
// Inline about if file missing — use embedded
if (!file_exists(__DIR__ . '/content/about.html')) {
	$about_body = as_replace_media_paths(
		'<h2>Our mission</h2><p>Nature-connected supports that honor dignity and capacity.</p><p>We serve individuals with autism and people with intellectual and developmental disabilities who benefit from structured, compassionate services—especially when support needs are high. Our mission is to foster belonging, joy, and meaningful participation through care farming and steady, honest practice.</p><p><strong>Commitment:</strong> We partner with families, guardians, payers, and community organizations using plain language, transparent planning, and respectful identity choices.</p><figure><img src="/media/edgefield-house.jpg" alt="Edgefield historic home and grounds in Albemarle County, Virginia." /></figure><h2>Care farm model &amp; philosophy</h2><p>Social-ecological care: land health and human flourishing together.</p><p>Autism Sanctuary operates on a social-ecological model: the well-being of our trails, gardens, and animals is intertwined with the psychosocial well-being of people. Care farming and therapeutic horticulture are structured modalities that create predictable, multisensory environments for learning, regulation, and meaningful contribution.</p><p>Nature-connected routines—planned with occupational and program guidance—offer movement, sound, scent, and texture that can help some people regulate and engage. Individual responses vary; we do not promise uniform medical outcomes.</p><h3>Shared work, shared joy</h3><p>People contribute to stewardship—caring for land, animals, and one another.</p><h3>From labels to real life</h3><p>We highlight strengths and valued roles alongside the hands-on supports people need.</p><h3>Evidence-based &amp; promising practices</h3><p>Training and documentation meet regulatory expectations and blend research-backed methods with approaches tested on this farm.</p><h2>Our home: Edgefield in White Hall, Virginia</h2><figure><img src="/media/edgefield-aerial.jpg" alt="Aerial view of the Edgefield property and surrounding land." /></figure><p>Autism Sanctuary operates from Edgefield—an extraordinary property and house stewarded by Frances Lee-Vandell. A 1780 home built by William Watkins was dismantled board by board and rebuilt onsite. Today it hosts events and informal gatherings amid animals, gardens, fruit trees, and maturing pine.</p><p><a href="/our-farm">See the farm &amp; trails</a> · <a href="/programs">View programs</a> · <a href="/contact">Contact us</a></p>'
	);
	// Fix media URLs manually
	$about_body = str_replace('/media/edgefield-house.jpg', esc_url($house), $about_body);
	$about_body = str_replace('/media/edgefield-aerial.jpg', esc_url($aerial), $about_body);
}

$pages['about'] = as_upsert_page(
	'about',
	'About',
	as_banner(
		'About Autism Sanctuary',
		'A rural care farm where people with significant support needs are seen, celebrated, and busy outdoors.',
		'Founded in 2020, Autism Sanctuary is a 501(c)(3) nonprofit and Virginia DBHDS-licensed provider on Pea Ridge Road.'
	) . as_prose_wrap($about_body)
);

$programs_body = str_replace(
	['/resources', '/admissions', '/contact?intent=program-fit'],
	['/resources/', '/admissions/', '/contact/?intent=program-fit'],
	'<p><strong>Authorization and eligibility:</strong> Service availability, waiver funding, and ISP authorization vary by person and payer. See <a href="/resources/">Resources</a> or <a href="/admissions/">Admissions</a>.</p><h2>Licensed service lines</h2><p>DBHDS-authorized supports delivered with skill—and a sense of fun.</p><h3>Group Day &amp; Community Coaching</h3><p>Weekday person-centered support in a natural learning environment: therapeutic horticulture, animal care, sensory-informed routines, and guided community engagement.</p><ul><li>Evidence-based practice in daily coaching and documentation</li><li>Small-group and side-by-side modeling with trained DSPs</li><li>Community outings at each person’s pace</li></ul><h3>Residential &amp; Home-Based Supports</h3><p>Lifespan support for adults who benefit from stable, individualized residential arrangements where authorized—Sponsored Residential, In-Home Support, and Supported Living pathways.</p><ul><li>Person-centered planning aligned with ISP goals</li><li>Trauma-informed, dignity-first daily living</li><li>Coordination with medical and community partners</li></ul><h3>Workplace Assistance</h3><p>Structured support for paid or volunteer community roles when that matches a person’s goals—including task analysis, workplace navigation, and retention supports.</p><ul><li>1:1 and small-team coaching</li><li>Employer partnership and natural supports</li><li>Focus on long-term success</li></ul><h2>Group day programming</h2><p>Full days outside for people with significant support needs.</p><p>Group Day gives people a planned routine with safe, structured programming under professional supervision. At Autism Sanctuary, that still means animals, trails, gardens, and playful routines—planned with the same care we bring to every licensed service day.</p><p><a href="/contact/?intent=program-fit">Explore a program fit</a> · <a href="/admissions/">Admissions</a> · <a href="/resources/">Waiver resources</a></p>'
);

$pages['programs'] = as_upsert_page(
	'programs',
	'Programs',
	as_banner('Programs & licensed services', 'Programs that embrace the outdoors.', 'Autism Sanctuary is a Virginia DBHDS-licensed farm serving people with autism and IDD—most of whom need substantial daily support.') . as_prose_wrap($programs_body)
);

$farm_body = '<h2>Why this place matters</h2><p>The farm shows how a working landscape can still meet every DBHDS expectation: structured trails for movement and pre-vocational routines, gardens for therapeutic horticulture, barns and fields for animal care, and porches for rest and regulation—all staffed by DSPs who like being outside.</p><ul><li>Days built for people with significant support needs</li><li>Training that blends regulations with dirt, weather, and livestock</li><li>Accessibility within a real working farm</li></ul><figure>' . ($aerial ? '<img src="' . esc_url($aerial) . '" alt="Aerial view of Edgefield gardens and surrounding land.">' : '') . '</figure><h2>Animals &amp; agriculture</h2><h3>Cattle</h3><p>Our cattle move regularly between pastures. Visitors often say hello through the fence; after a long, happy life, grass-fed cattle are processed into beef available through local partners and market tables when offered.</p><h3>Chickens</h3><p>Chickens roam fields in warmer weather and stay in the fenced garden in winter. Caring for them helps teach empathy—and their eggs appear at farmers market visits.</p><h3>High tunnel &amp; gardens</h3><p>Our 30′×72′ high tunnel extends the growing season and anchors garden work, composting, and hands-on horticulture that people can return to week after week.</p><figure>' . ($animals ? '<img src="' . esc_url($animals) . '" alt="Animals and outdoor life on the Autism Sanctuary farm.">' : '') . '</figure><h2>Trails: Enchanted Forest &amp; Spring Creek</h2><p>Immersing people in nature supports education, regulation, and development. We currently offer two walking trails on the property.</p><h3>Enchanted Forest Trail</h3><p>A ¾-mile loop through forested land—more groomed and less sloped, with learning stations that promote skill building and creativity. Realized with support from CACF’s Enriching Communities Grant and volunteer partners.</p><h3>Spring Creek Trail</h3><p>A longer route that diverges downhill along Spring Creek. Steeper and more challenging, with a peaceful, engaging walk beside the water.</p><h2>Volunteer with us</h2><p>Volunteers support trail maintenance (raking, clearing debris, marking invasive species), agriculture in the high tunnel and gardens, and—with training and a weekly commitment—assistance alongside staff in the adult day program.</p><p>Volunteer roles complement, never replace, paid DSP staffing. We match tasks to season, capacity, and safety.</p><p><a href="/contact/?intent=volunteer">Inquire about volunteering</a> · <a href="/contact/">Contact us</a> · <a href="/donate/">Support the farm</a></p>';

$pages['our-farm'] = as_upsert_page(
	'our-farm',
	'Our farm',
	as_banner('Pea Ridge Road · Western Albemarle', 'Our home—where the farm is the program.', 'Roughly 85 acres of trails, animals, gardens, and gathering spaces woven into Crozet, White Hall, and the wider Albemarle community.') . as_prose_wrap($farm_body)
);

$admissions_body = '<h2>How we partner with you</h2><h3>Step 1: Intake materials</h3><p>Share information so we can understand strengths, goals, and supports.</p><h3>Step 2: Collaborative review</h3><p>Program leaders evaluate fit, safety, and service alignment.</p><h3>Step 3: Person-centered meeting</h3><p>We meet with people (when appropriate) and supporters to co-design next steps.</p><h3>Step 4: Authorized start</h3><p>With ISP approval as applicable, we schedule onboarding into the right mix of supports.</p><h2>Questions families often ask</h2><h3>Who can begin the admissions process?</h3><p>Family members, guardians, case managers, and authorized professionals seeking lifespan support for individuals with autism or IDD may start the conversation.</p><h3>Do you maintain waitlists?</h3><p>Some programs may have wait times depending on staffing ratios and licensure capacity. We communicate transparently about anticipated starts.</p><h3>Are you a nonprofit and licensed provider?</h3><p>Yes. Autism Sanctuary is a 501(c)(3) nonprofit and a Virginia DBHDS-licensed provider.</p><h3>What documents help us plan?</h3><p>When applicable, current ISP, positive behavior support plans, and educational or psychological evaluations from the past two years assist our team.</p><p>Use the inquiry form below to explore a program fit. For the full multi-step intake still in production, you may also use <a href="https://www.autismsanctuary.org/intake-form/">the current intake form</a>.</p><h2>Explore a program fit</h2>' . $gf_inquiry;

$pages['admissions'] = as_upsert_page(
	'admissions',
	'Admissions',
	as_banner('Admissions', 'Begin with a conversation rooted in respect and clarity.', 'Our admissions team partners with families, guardians, and case managers—and people when it fits.') . as_prose_wrap($admissions_body)
);

$people_body = '<p>Meet the volunteer board and staff leaders who guide Autism Sanctuary’s mission, programs, and daily operations.</p>
<h2>Board of Directors</h2>
<div class="as-people">
  <div class="as-person"><h3>Jason Brewster</h3><p class="as-person__role">President</p><p>Biography coming soon.</p></div>
  <div class="as-person"><h3>Robert Kreps</h3><p class="as-person__role">Treasurer</p><p>Biography coming soon.</p></div>
  <div class="as-person"><h3>Matthew Osborne</h3><p class="as-person__role">Secretary</p><p>Biography coming soon.</p></div>
  <div class="as-person"><h3>Rose Neville</h3><p class="as-person__role">Board member</p><p>Biography coming soon.</p></div>
</div>
<h2>Management</h2>
<div class="as-people">
  <div class="as-person"><h3>Olivia Bruno</h3><p class="as-person__role">Executive Director</p><p>Biography coming soon.</p></div>
  <div class="as-person"><h3>Isabelle (Izzy) Kueser</h3><p class="as-person__role">Director of Adult Services</p><p>Biography coming soon.</p></div>
</div>';

$pages['people'] = as_upsert_page(
	'people',
	'People',
	as_banner('People', 'The board and team who steward Autism Sanctuary.', 'Governance and day-to-day leadership for our nonprofit care farm.') . as_prose_wrap($people_body)
);

$resources_body = '<h2>Referral &amp; application pathway</h2><ol><li><strong>Review programs:</strong> Read <a href="/programs/">Programs</a>.</li><li><strong>Contact admissions:</strong> Email <a href="mailto:info@autismsanctuary.org">info@autismsanctuary.org</a> or call <a href="tel:+14342072118">(434) 207-2118</a>.</li><li><strong>Complete intake:</strong> Start via <a href="/admissions/">Admissions</a>.</li><li><strong>Plan authorization:</strong> We coordinate with case managers and CSBs to confirm funding alignment.</li></ol><h2>Waivers and individualized authorization</h2><h3>Medicaid waivers (overview)</h3><p>Many people access services through Virginia’s Medicaid waiver programs—commonly Family &amp; Individual Supports (FIS), Building Independence (BI), and Community Living (CL). Each has distinct eligibility rules, budgets, and service definitions.</p><p><strong>Your case manager</strong> determines whether a specific Autism Sanctuary service can be authorized on an ISP.</p><p><strong>Heads-up:</strong> Autism Sanctuary does not coordinate Children’s Services Act (CSA) funding. If CSA is part of your conversation, your case manager can help identify appropriate providers.</p><h2>Accessibility &amp; transportation</h2><p>Inclusive access within a working farm.</p><p>Trails, gardens, and program buildings are checked for safe use; individualized plans may include adaptive equipment, pacing, or alternative activities when weather or terrain shifts.</p><p>Transportation arrangements vary; talk with admissions about how people typically arrive and what supports are realistic for your situation.</p><p><a href="/contact/?intent=program-fit">Send an inquiry</a> · <a href="/admissions/">Admissions</a></p>';

$pages['resources'] = as_upsert_page(
	'resources',
	'Resources',
	as_banner('Resources & guidance', 'Funding, referrals, and getting here—without the jargon storm.', 'Informational pathways for families and case managers. Not legal advice.') . as_prose_wrap($resources_body)
);

$careers_body = '<h2>Direct Support Professionals</h2><p>Be the teammate who makes farm days possible.</p><p>DSPs provide hands-on support across day, residential, and vocational settings—coaching people through therapeutic horticulture, pre-vocational routines, and community outings.</p><ul><li>Training in evidence-based approaches plus Sanctuary-developed practices</li><li>Mentorship alongside program leadership</li><li>Pathways aligned with nursing, OT, social work, education, and related fields</li></ul><p><a href="/fellowship/">Siemers Fellowship</a></p><h2>Volunteers</h2><p>Help with trail maintenance, agriculture in the high tunnel and gardens, or—with training—assist staff in the adult day program. Roles are informal and complement paid staffing.</p><p><a href="/our-farm/">Learn about the farm</a></p><p>Use the inquiry form below and select <strong>Job opportunities</strong> or <strong>Volunteering</strong> so we can route your note.</p>' . $gf_inquiry;

$pages['careers'] = as_upsert_page(
	'careers',
	'Careers & volunteers',
	as_banner('Join our community', 'Careers & volunteers: show up for people who love the outdoors.', 'We hire Direct Support Professionals who are steady, kind, and skilled with significant support needs.') . as_prose_wrap($careers_body)
);

$fellowship_body = '<h2>Learning beside staff who live in boots, not just textbooks</h2><p>Fellows collaborate with DSPs to support people—most of whom have significant support needs—across day services, residential contexts when appropriate, and pre-vocational routines on trails and in therapeutic horticulture.</p><ul><li>Exposure aligned with nursing, SLP, psychology, social work, pre-med, and related pathways</li><li>Supervision emphasizing documentation, ethics, and respectful boundaries</li><li>Commitment to neuro-inclusive language and humble listening to families</li></ul><h3>Real-world grounding</h3><p>Structured exposure to rural IDD supports with licensed leadership and clear boundaries.</p><h3>Leadership habits</h3><p>Communication, coordination, and humility in fast-moving community settings.</p><h3>Community impact</h3><p>Contributions that strengthen continuity for people and families.</p><p><a href="/contact/?intent=jobs">Inquire</a> · <a href="/careers/">Careers &amp; volunteers</a></p>';

$pages['fellowship'] = as_upsert_page(
	'fellowship',
	'Siemers Fellowship',
	as_banner('Workforce & leadership formation', 'Siemers Fellowship', 'Immersive time on our farm for emerging professionals.') . as_prose_wrap($fellowship_body)
);

$donate_body = '<p>Philanthropy fills gaps Medicaid and grants can miss: trails, barns, gardens, sensory play, and staff development that keep outdoor programming safe and fun.</p>
<div class="as-grid as-grid--3" style="margin:2rem 0">
  <div class="as-feature"><h3>Care farming infrastructure</h3><p>Trails, barns, gardens, and sensory spaces that make outdoor days possible.</p></div>
  <div class="as-feature"><h3>Participant access</h3><p>Support that helps people with significant needs take part fully in farm life.</p></div>
  <div class="as-feature"><h3>Workforce strength</h3><p>Training and retention for DSPs who show up in boots, rain or shine.</p></div>
</div>
<p><strong>Note:</strong> Online Stripe checkout will be connected next. Share your gift preference below and we will follow up with secure payment instructions. You can also email <a href="mailto:info@autismsanctuary.org">info@autismsanctuary.org</a> or call <a href="tel:+14342072118">(434) 207-2118</a>.</p>
' . $gf_donate;

$pages['donate'] = as_upsert_page(
	'donate',
	'Donate',
	as_banner('Philanthropy', 'Help us keep farm days generous and joyful.', 'Give in a way that fits—and tell us how you would like your gift used.') . as_prose_wrap($donate_body)
);

$thanks_content = as_banner('Thank you', 'Your generosity keeps farm days joyful.', 'We appreciate your support of Autism Sanctuary.') . as_prose_wrap('<p>If you submitted a gift inquiry, we will follow up shortly with next steps. Questions? Email <a href="mailto:info@autismsanctuary.org">info@autismsanctuary.org</a>.</p>' . as_actions([['Return home', '/'], ['Explore the farm', '/our-farm/', 'ghost']]));
$thanks = get_page_by_path('donate/thanks');
if ($thanks) {
	wp_update_post(['ID' => $thanks->ID, 'post_content' => $thanks_content, 'post_status' => 'publish']);
	$pages['donate-thanks'] = (int) $thanks->ID;
} else {
	$pages['donate-thanks'] = (int) wp_insert_post([
		'post_title'   => 'Thank you',
		'post_name'    => 'thanks',
		'post_parent'  => $pages['donate'],
		'post_content' => $thanks_content,
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'post_author'  => 1,
	]);
}
update_post_meta($pages['donate-thanks'], '_et_pb_page_layout', 'et_no_sidebar');
update_post_meta($pages['donate-thanks'], '_et_pb_show_title', 'off');
as_log('Thanks page #' . $pages['donate-thanks']);

$contact_body = '<div class="as-contact-grid"><div class="as-aside">
<p><strong>Visit</strong><br>2860 Pea Ridge Road<br>Charlottesville, VA 22901</p>
<p><strong>Call</strong><br><a href="tel:+14342072118">(434) 207-2118</a></p>
<p><strong>Email</strong><br><a href="mailto:info@autismsanctuary.org">info@autismsanctuary.org</a></p>
<p><a href="https://www.instagram.com/autismsanctuary">Instagram</a> · <a href="https://www.facebook.com/autismsanctuary">Facebook</a></p>
' . ($house ? '<figure style="margin-top:1.5rem"><img src="' . esc_url($house) . '" alt="Edgefield farmhouse"></figure>' : '') . '
</div><div><h2>Send an inquiry</h2>' . $gf_inquiry . '</div></div>';

$pages['contact'] = as_upsert_page(
	'contact',
	'Contact',
	as_banner('Contact', 'We are glad you reached out.', 'Ask about programs, volunteering, jobs, donating, or general information.') . as_prose_wrap($contact_body)
);

$pages['privacy'] = as_upsert_page(
	'privacy',
	'Privacy',
	as_banner('Legal', 'Privacy', 'How we handle information you share with us.') . as_prose_wrap('<p>Autism Sanctuary collects contact information you submit through inquiry and donation forms to respond to your request and process gifts. We do not sell personal information. When online payments are enabled, payment details are processed by Stripe and are not stored on this site.</p><p>For privacy questions, email <a href="mailto:info@autismsanctuary.org">info@autismsanctuary.org</a>.</p>')
);

$pages['terms'] = as_upsert_page(
	'terms',
	'Terms',
	as_banner('Legal', 'Terms', 'Informational disclaimer for this website.') . as_prose_wrap('<p>Content on this website is for general information about Autism Sanctuary’s programs and property. It is not medical, legal, or eligibility advice. Service authorization depends on individualized planning and payer rules.</p><p>Questions: <a href="mailto:info@autismsanctuary.org">info@autismsanctuary.org</a>.</p>')
);

$pages['news'] = as_upsert_page(
	'news',
	'News & updates',
	as_banner('News', 'News & updates', 'Stories from the farm and our community.') . as_prose_wrap('<p>See the latest posts below, or follow us on <a href="https://www.instagram.com/autismsanctuary">Instagram</a>.</p><!-- news index uses Posts page -->')
);

// Front page / posts
update_option('show_on_front', 'page');
update_option('page_on_front', $pages['home']);
update_option('page_for_posts', $pages['news']);
as_log('Front page = Home; Posts = News');

// Delete sample page
$sample = get_page_by_path('sample-page');
if ($sample) {
	wp_delete_post($sample->ID, true);
	as_log('Removed Sample Page');
}

// Update donate form confirmation to thanks page
if ($donate_id && class_exists('GFAPI') && !empty($pages['donate-thanks'])) {
	$form = GFAPI::get_form($donate_id);
	if ($form) {
		foreach ($form['confirmations'] as $cid => &$conf) {
			$conf['type'] = 'page';
			$conf['pageId'] = (string) $pages['donate-thanks'];
			$conf['message'] = '';
		}
		unset($conf);
		GFAPI::update_form($form);
		as_log('Donate confirmation → thanks page');
	}
}

// ---------------------------------------------------------------------------
// 6. Menus
// ---------------------------------------------------------------------------
function as_menu_ensure($name) {
	$menu = wp_get_nav_menu_object($name);
	if ($menu) {
		$items = wp_get_nav_menu_items($menu->term_id);
		if ($items) {
			foreach ($items as $item) {
				wp_delete_post($item->ID, true);
			}
		}
		return (int) $menu->term_id;
	}
	return (int) wp_create_nav_menu($name);
}

function as_menu_add($menu_id, $title, $url, $classes = []) {
	wp_update_nav_menu_item($menu_id, 0, [
		'menu-item-title'  => $title,
		'menu-item-url'    => $url,
		'menu-item-status' => 'publish',
		'menu-item-type'   => 'custom',
		'menu-item-classes'=> implode(' ', $classes),
	]);
}

$primary = as_menu_ensure('Primary');
$home_url = home_url('/');
as_menu_add($primary, 'About', home_url('/about/'));
as_menu_add($primary, 'People', home_url('/people/'));
as_menu_add($primary, 'Programs', home_url('/programs/'));
as_menu_add($primary, 'Our farm', home_url('/our-farm/'));
as_menu_add($primary, 'Admissions', home_url('/admissions/'));
as_menu_add($primary, 'Careers', home_url('/careers/'));
as_menu_add($primary, 'Contact', home_url('/contact/'));
as_menu_add($primary, 'Donate', home_url('/donate/'), ['as-donate-nav', 'cta-donate']);

$footer = as_menu_ensure('Footer');
as_menu_add($footer, 'About', home_url('/about/'));
as_menu_add($footer, 'Programs', home_url('/programs/'));
as_menu_add($footer, 'Our farm', home_url('/our-farm/'));
as_menu_add($footer, 'Resources', home_url('/resources/'));
as_menu_add($footer, 'News', home_url('/news/'));
as_menu_add($footer, 'Admissions', home_url('/admissions/'));
as_menu_add($footer, 'Careers', home_url('/careers/'));
as_menu_add($footer, 'Fellowship', home_url('/fellowship/'));
as_menu_add($footer, 'Donate', home_url('/donate/'));
as_menu_add($footer, 'Contact', home_url('/contact/'));
as_menu_add($footer, 'Privacy', home_url('/privacy/'));
as_menu_add($footer, 'Terms', home_url('/terms/'));

$locations = get_theme_mod('nav_menu_locations', []);
$locations['primary-menu'] = $primary;
$locations['footer-menu'] = $footer;
set_theme_mod('nav_menu_locations', $locations);
as_log('Menus assigned');

// ---------------------------------------------------------------------------
// 7. Import news from live WP
// ---------------------------------------------------------------------------
as_import_news_from_live();

function as_import_news_from_live() {
	$dir = __DIR__ . '/news-export';
	if (!is_dir($dir)) {
		as_log('No news-export/ directory — run import-news.php after exporting from live WP');
		return;
	}
	as_log('News: use wp eval-file wordpress-migration/import-news.php');
}

// ---------------------------------------------------------------------------
// 8. Footer credits / widgets text
// ---------------------------------------------------------------------------
$footer_text = 'Autism Sanctuary is a 501(c)(3) nonprofit and Virginia DBHDS-licensed care farm. 2860 Pea Ridge Road, Charlottesville, VA 22901 · (434) 207-2118 · info@autismsanctuary.org';
$et = get_option('et_divi', []);
if (!is_array($et)) {
	$et = [];
}
$et['footer_credits'] = true;
$et['custom_footer_credits'] = $footer_text;
$et['show_footer_social_icons'] = 'on';
update_option('et_divi', $et);

// Soft 404 / reading helpers via Redirection if available
if (class_exists('Red_Item')) {
	as_log('Redirection plugin present — add rules in WP admin if needed');
}

as_log('=== Migration complete ===');
as_log('Staging: ' . home_url('/'));
as_log('Inquiry form ID: ' . $inquiry_id);
as_log('Donate form ID: ' . $donate_id);
