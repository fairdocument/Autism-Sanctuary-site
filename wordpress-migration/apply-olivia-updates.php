<?php
/**
 * Apply Olivia 8/24 website feedback + locked decisions.
 * Run: wp eval-file wordpress-migration/apply-olivia-updates.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

echo "=== Apply Olivia website updates ===\n";

$admins = get_users(['role' => 'administrator', 'number' => 1]);
if ($admins) {
	wp_set_current_user((int) $admins[0]->ID);
}

function as_olivia_log($msg) {
	echo $msg . "\n";
}

function as_olivia_media($filename) {
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

function as_olivia_btn($label, $href, $style = 'primary') {
	$class = $style === 'ghost' ? 'as-btn as-btn--ghost' : 'as-btn as-btn--primary';
	return '<a class="' . $class . '" href="' . esc_url($href) . '">' . esc_html($label) . '</a>';
}

function as_olivia_actions($buttons) {
	$html = '<div class="as-actions">';
	foreach ($buttons as $b) {
		$html .= as_olivia_btn($b[0], $b[1], $b[2] ?? 'primary');
	}
	$html .= '</div>';
	return $html;
}

function as_olivia_banner($eyebrow, $title, $lede = '') {
	$html = '<section class="as-banner"><div class="as-page">';
	$html .= '<p class="as-eyebrow">' . esc_html($eyebrow) . '</p>';
	$html .= '<h1>' . esc_html($title) . '</h1>';
	if ($lede) {
		$html .= '<p class="as-lede">' . esc_html($lede) . '</p>';
	}
	$html .= '</div></section>';
	return $html;
}

function as_olivia_prose($inner) {
	return '<div class="as-page as-prose">' . $inner . '</div>';
}

function as_olivia_placeholder($label) {
	return '<div class="as-img-placeholder" role="img" aria-label="' . esc_attr($label) . '"><span>' . esc_html($label) . '</span></div>';
}

function as_olivia_upsert($slug, $title, $content, $excerpt = '') {
	$page = get_page_by_path($slug);
	$args = [
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_content' => $content,
		'post_excerpt' => $excerpt,
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'post_author'  => 1,
	];
	if ($page) {
		$args['ID'] = $page->ID;
		$id = wp_update_post($args, true);
	} else {
		$id = wp_insert_post($args, true);
	}
	if (is_wp_error($id)) {
		as_olivia_log("ERROR /{$slug}/: " . $id->get_error_message());
		return 0;
	}
	update_post_meta($id, '_et_pb_use_builder', 'off');
	update_post_meta($id, '_et_pb_page_layout', 'et_no_sidebar');
	update_post_meta($id, '_et_pb_show_title', 'off');
	delete_post_meta($id, '_et_builder_version');
	as_olivia_log("OK /{$slug}/ (#{$id})");
	return (int) $id;
}

function as_olivia_inquiry_shortcode() {
	if (!class_exists('GFAPI')) {
		return '<p><a href="mailto:info@autismsanctuary.org">Email info@autismsanctuary.org</a></p>';
	}
	$forms = GFAPI::get_forms(true);
	$id = 0;
	foreach ($forms as $f) {
		$title = strtolower($f['title'] ?? '');
		if (strpos($title, 'inquiry') !== false || strpos($title, 'contact') !== false) {
			$id = (int) $f['id'];
			break;
		}
	}
	if (!$id && $forms) {
		$id = (int) $forms[0]['id'];
	}
	return $id
		? '[gravityform id="' . $id . '" title="false" description="false" ajax="true"]'
		: '<p><a href="mailto:info@autismsanctuary.org">Email info@autismsanctuary.org</a></p>';
}

function as_olivia_donate_shortcode() {
	if (!class_exists('GFAPI')) {
		return '';
	}
	$forms = GFAPI::get_forms(true);
	foreach ($forms as $f) {
		$title = strtolower($f['title'] ?? '');
		if (strpos($title, 'donate') !== false || strpos($title, 'gift') !== false) {
			return '[gravityform id="' . (int) $f['id'] . '" title="false" description="false" ajax="true"]';
		}
	}
	return '';
}

$aerial = as_olivia_media('edgefield-aerial.jpg');
$house = as_olivia_media('edgefield-house.jpg');
$gardens = as_olivia_media('sanctuary-gardens.jpg');
$forest = as_olivia_media('hero-enchanted-forest.jpg');
$animals = as_olivia_media('farm-animals.jpg');
$video = as_olivia_media('hero-farm.mp4');
$gf_inquiry = as_olivia_inquiry_shortcode();
$gf_donate = as_olivia_donate_shortcode();

$our_home_p1 = 'Autism Sanctuary operates from Edgefield—an extraordinary property and house stewarded by Frances Lee-Vandell. A 1780 home built by William Watkins was dismantled board by board and rebuilt onsite.';
$our_home_p2 = 'Today, the property features a range of animals, a robust garden, walking trails, and a variety of activity stations. Thank you to Frances’ incredible generosity, this property and land have become the foundation of our programs and have changed the lives of many.';

// ---------------------------------------------------------------------------
// Home
// ---------------------------------------------------------------------------
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
    <h1>Redefining support through nature and purpose</h1>
    <p class="as-lede">A working farm in the foothills of the Blue Ridge where people with developmental disabilities are supported and empowered to grow in nature.</p>
    ' . as_olivia_actions([['See our services', '/programs/'], ['Visit our farm story', '/our-farm/', 'ghost']]) . '
  </div>
</section>

<section class="as-section as-section--cream">
  <div class="as-section__inner as-split">
    <div>
      <p class="as-eyebrow">Our approach</p>
      <h2>Purpose and belonging, rooted in nature.</h2>
      <p class="as-lede">People in our program engage with trails, animals, gardens, and staff who care. Days are designed around a sense of purpose and belonging, with an emphasis on growth and meaningful relationships.</p>
      ' . as_olivia_actions([['About Autism Sanctuary', '/about/'], ['Ask about a fit', '/contact/?intent=program-fit', 'ghost']]) . '
    </div>
    <div class="as-split__media">' . ($gardens ? '<img src="' . esc_url($gardens) . '" alt="Garden paths and plantings at Autism Sanctuary’s Virginia farm.">' : as_olivia_placeholder('Photo placeholder: gardens and outdoor program')) . '</div>
  </div>
</section>

<section class="as-section as-section--meadow">
  <div class="as-section__inner">
    <p class="as-eyebrow">Our Services</p>
    <h2>Licensed support rooted in connection, growth, and belonging.</h2>
    <div class="as-grid as-grid--3">
      <div class="as-feature"><h3>Group Day</h3><p>On-site weekday support on the farm—animal care, gardens, trails, and skill-building in nature.</p></div>
      <div class="as-feature"><h3>Community Coaching</h3><p>1:1 support in the community to build skills that open doors to everyday participation.</p></div>
      <div class="as-feature"><h3>Community Engagement</h3><p>1:3 small-group support for meaningful outings and community life beyond the farm.</p></div>
      <div class="as-feature"><h3>Residential &amp; Home-Based</h3><p>Personalized supports in people’s homes and community settings where authorized.</p></div>
      <div class="as-feature"><h3>Workplace Assistance</h3><p>1:1 on-the-job support that helps people succeed in meaningful employment.</p></div>
    </div>
    ' . as_olivia_actions([['Full service details', '/programs/']]) . '
  </div>
</section>

<section class="as-section">
  <div class="as-section__inner as-split as-split--reverse">
    <div>
      <p class="as-eyebrow">Our home</p>
      <h2>Edgefield in the Blue Ridge foothills.</h2>
      <p class="as-lede">' . esc_html($our_home_p1) . '</p>
      <p class="as-lede">' . esc_html($our_home_p2) . '</p>
      ' . as_olivia_actions([['Explore the property', '/our-farm/'], ['Volunteer with us', '/contact/?intent=volunteer', 'ghost']]) . '
    </div>
    <div class="as-split__media">' . ($house ? '<img src="' . esc_url($house) . '" alt="Historic Edgefield farmhouse at Autism Sanctuary.">' : as_olivia_placeholder('Photo placeholder: Edgefield home')) . '</div>
  </div>
</section>

<section class="as-section as-section--cream">
  <div class="as-section__inner as-split">
    <div>
      <p class="as-eyebrow">Looking ahead</p>
      <h2>Growing capacity for purpose and belonging.</h2>
      <p class="as-lede">With your support, we are advancing capital and program priorities that strengthen life at Autism Sanctuary.</p>
      <ul class="as-checklist">
        <li><strong>Activity Barn</strong> a purpose-built program space that supports growth and moves day programming out of the historic house basement.</li>
        <li><strong>Facility upgrades</strong> improvements to existing campus resources that keep outdoor and indoor days safe, welcoming, and ready for more people.</li>
        <li><strong>Workplace opportunities under development</strong> expanding vocational pathways and Workplace Assistance so more people can build skills and meaningful work.</li>
      </ul>
      ' . as_olivia_actions([['Support this work', '/donate/']]) . '
    </div>
    <div class="as-split__media">' . as_olivia_placeholder('Photo placeholder: Activity Barn and facility vision') . '</div>
  </div>
</section>

<section class="as-section">
  <div class="as-section__inner as-split as-split--reverse">
    <div>
      <p class="as-eyebrow">Support the sanctuary</p>
      <h2>Your gift keeps farm days joyful and safe.</h2>
      <p class="as-lede">Philanthropy strengthens trails, barns, gardens, sensory spaces, staff development, and the programs that make Autism Sanctuary possible—alongside authorized services and private-pay arrangements where families choose that path.</p>
      ' . as_olivia_actions([['Donate', '/donate/'], ['Ask about giving', '/contact/?intent=donate', 'ghost']]) . '
    </div>
    <div class="as-split__media">' . ($forest ? '<img src="' . esc_url($forest) . '" alt="Forest canopy along the Enchanted Forest trail.">' : as_olivia_placeholder('Photo placeholder: trails and nature')) . '</div>
  </div>
</section>

<section class="as-section as-section--forest">
  <div class="as-section__inner">
    <h2>Ready to talk?</h2>
    <p class="as-lede">Whether you are exploring services, volunteering, careers, or a gift—we are glad you reached out.</p>
    ' . as_olivia_actions([['Send an inquiry', '/contact/'], ['Explore services', '/programs/', 'ghost']]) . '
  </div>
</section>
';

as_olivia_upsert(
	'home',
	'Care Farm in Western Albemarle',
	$home_html,
	'Autism Sanctuary is a Virginia DBHDS-licensed working farm in Western Albemarle.'
);

// ---------------------------------------------------------------------------
// About
// ---------------------------------------------------------------------------
$about_body = '
<h2>Our mission</h2>
<p>Provide a safe and therapeutic place in nature for individuals with autism and their families to recreate and connect.</p>

<h2>Our vision</h2>
<p>Create a world where all neurodivergent people are recognized, celebrated, and supported.</p>

<h2>Our values — BEE NICE</h2>
<ul class="as-checklist">
  <li><strong>Belonging</strong> ensuring that every individual feels accepted and included.</li>
  <li><strong>Equality</strong> providing every person the opportunity to enjoy nature.</li>
  <li><strong>Environment</strong> prioritizing the restoration and stewardship of nature.</li>
  <li><strong>Nature</strong> fostering a deep appreciation for the natural world.</li>
  <li><strong>Integrity</strong> behaving ethically and honestly in all we do.</li>
  <li><strong>Community</strong> cultivating a supportive and connected network.</li>
  <li><strong>Empowerment</strong> providing tools and resources for neurodivergent individuals to thrive.</li>
</ul>

<figure>' . as_olivia_placeholder('Photo placeholder: replace Pete and Frances image') . '</figure>

<h2>Our story</h2>
<p>Founded in 2020, Autism Sanctuary began as a vision to create a place where individuals with developmental disabilities could connect with nature, build community, and experience meaningful growth.</p>
<p>The Brewster family saw an opportunity for inclusive, nature-based programming for their high-support-needs son and others like him who lacked access to community opportunities and services. Working with their neighbor, Frances Lee-Vandell, their collective vision led to a living sanctuary that offers support, purpose, and belonging.</p>
<p>In December 2023, Autism Sanctuary became a Virginia DBHDS-licensed service provider. Since then, we have grown to meet the needs of our community and now offer day programs, residential services, community-based supports, and workplace assistance. In 2025, Autism Sanctuary received the Charlottesville Business Innovation Council Social Impact Award.</p>
<p>As we began serving families, we continued to identify gaps in services and expand our programs and offerings to meet the diverse needs of individuals and families in multiple ways. We now offer off-site residential and community-based services in addition to our on-site day program.</p>
<p>We pair passion and commitment with nature to create a therapeutic and active environment—and foster meaningful engagement through nature-based skill-building activities.</p>

<h2>Our home: Edgefield in White Hall, Virginia</h2>
<figure>' . ($aerial ? '<img src="' . esc_url($aerial) . '" alt="Aerial view of the Edgefield property and surrounding land.">' : as_olivia_placeholder('Photo placeholder: Edgefield aerial')) . '</figure>
<p>' . esc_html($our_home_p1) . '</p>
<p>' . esc_html($our_home_p2) . '</p>
<p><a href="/our-farm/">See the farm &amp; trails</a> · <a href="/programs/">View services</a> · <a href="/contact/">Contact us</a></p>
';

as_olivia_upsert(
	'about',
	'About',
	as_olivia_banner(
		'About Autism Sanctuary',
		'A farm-based program where people with developmental disabilities are seen, supported, and busy outdoors.',
		'We pair passion and commitment with nature to create a therapeutic and active environment.'
	) . as_olivia_prose($about_body),
	'A farm-based program where people with developmental disabilities are seen, supported, and busy outdoors.'
);

// ---------------------------------------------------------------------------
// Programs (Services) + interest callout
// ---------------------------------------------------------------------------
$programs_body = '
<p><strong>Authorization and eligibility:</strong> Service availability, funding, and authorization vary by person and payer. Many people access services through Virginia’s Medicaid waiver programs; private-pay arrangements are also available. See <a href="/resources/">Resources</a> or use the interest form below.</p>

<h2>Licensed service lines</h2>
<p>Autism Sanctuary is a Virginia DBHDS-licensed provider serving adults with developmental disabilities. Licensed services show up both alongside our farming operations as well as out in the community and in people’s homes, creating a more integrated support structure tailored to each individual’s needs.</p>
<p>A range of support ratios is provided based on individual support needs.</p>

<h3>Group Day</h3>
<p>On-site weekday person-centered support in a natural learning environment: therapeutic horticulture, animal care, sensory-informed routines, and skill-building on the farm. A typical day runs Monday through Friday, about 9 AM–3 PM.</p>
<ul>
  <li>Small-group and side-by-side modeling with trained DSPs</li>
  <li>Trails, gardens, animals, and hands-on farm activities</li>
</ul>

<h3>Community Coaching (1:1)</h3>
<p>Off-site, individualized support that helps people build the social and situational skills needed to participate more independently in community settings. Community Coaching does not take place on the farm.</p>
<ul>
  <li>1:1 coaching tailored to each person’s goals</li>
  <li>Practice in real community environments at each person’s pace</li>
</ul>

<h3>Community Engagement (1:3)</h3>
<p>Off-site small-group support for meaningful community participation—libraries, local businesses, other farms, nature trails, and shared activities. Community Engagement does not take place on the farm.</p>
<ul>
  <li>1:3 support ratio</li>
  <li>Guided outings that build confidence and connection</li>
</ul>

<h3>Residential &amp; Home-Based Supports</h3>
<p>Lifespan support for adults who benefit from stable, individualized residential arrangements where authorized—including Sponsored Residential, In-Home Support, and Supported Living. Person-centered planning aligned with each individual’s goals stands true across these services.</p>

<h3>Workplace Assistance</h3>
<p>Structured 1:1 support for paid or volunteer community roles when that matches a person’s goals—including task analysis, workplace navigation, and retention supports.</p>
<ul>
  <li>1:1 support</li>
  <li>Employer partnership</li>
  <li>Focus on long-term success</li>
</ul>

<h2 id="interest">Explore a program fit</h2>
<p>Our admissions team works closely with individuals, families, guardians, and case managers to understand each person’s goals, strengths, and needs and identify the supports that are the best fit.</p>
<p>Use the inquiry form below to start the conversation. For the full multi-step intake still in production, you may also use <a href="https://www.autismsanctuary.org/intake-form/">the current intake form</a>.</p>
' . $gf_inquiry . '
<p><a href="/resources/">Waiver resources</a> · <a href="/contact/?intent=program-fit">Contact us</a></p>
';

as_olivia_upsert(
	'programs',
	'Programs',
	as_olivia_banner(
		'Programs &amp; licensed services',
		'Licensed support rooted in connection, growth, and belonging.',
		'Autism Sanctuary is a Virginia DBHDS-licensed provider serving adults with developmental disabilities—on the farm, in the community, and in people’s homes.'
	) . as_olivia_prose($programs_body),
	'Licensed supports on the farm, in the community, and in people’s homes.'
);

// ---------------------------------------------------------------------------
// Our farm
// ---------------------------------------------------------------------------
$farm_body = '
<h2>Why this place matters</h2>
<p>Our operating farm offers meaningful engagement and skill development grounded in nature and farming practices. Activities include immersive trails that encourage movement and exploration, gardens that support therapeutic skill-building, animal care opportunities, and a range of hands-on activities designed to build confidence, independence, practical skills, and a deeper connection with nature.</p>
<figure>' . ($aerial ? '<img src="' . esc_url($aerial) . '" alt="Aerial view of Edgefield gardens and surrounding land.">' : as_olivia_placeholder('Photo placeholder: farm aerial')) . '</figure>

<h2>Animals &amp; agriculture</h2>
<h3>Cattle</h3>
<p>Our cattle move regularly between pastures, enjoying days in the sun and plenty of room to explore. People in our program visit the herd regularly, offering gentle pets, scratches, and snacks while building familiarity and connection. After a long, happy life, our grass-fed cattle are processed into beef made available directly from our farm and through local partners and our farmers market tables.</p>
<h3>Chickens</h3>
<p>Chickens roam fields in warmer weather and stay in the fenced garden in winter. Caring for them helps teach empathy—and their eggs appear at farmers market visits.</p>
<h3>High tunnel &amp; gardens</h3>
<p>Our 30′×72′ high tunnel extends the growing season and anchors garden work, composting, and hands-on horticulture that people can return to week after week.</p>
<figure>' . ($animals ? '<img src="' . esc_url($animals) . '" alt="Animals and outdoor life on the Autism Sanctuary farm.">' : as_olivia_placeholder('Photo placeholder: farm animals')) . '</figure>

<h2>Trails: Enchanted Forest &amp; Spring Creek</h2>
<p>Immersing people in nature supports education, regulation, and development. We currently offer two walking trails on the property.</p>
<h3>Enchanted Forest Trail</h3>
<p>A ¾-mile loop through forested land—more groomed and less sloped, with learning stations that promote skill building and creativity. Realized with support from CACF’s Enriching Communities Grant and volunteer partners.</p>
<h3>Spring Creek Trail</h3>
<p>A longer route that diverges downhill along Spring Creek. Steeper and more challenging, with a peaceful, engaging walk beside the water.</p>

<h2>Volunteer with us</h2>
<p>Volunteers support trail maintenance (raking, clearing debris, marking invasive species), agriculture in the high tunnel and gardens, and—with training and a weekly commitment—assistance alongside staff in the adult day program.</p>
<p>Volunteer roles complement, never replace, paid DSP staffing. We match tasks to season, capacity, and safety.</p>
<p><a href="/contact/?intent=volunteer">Inquire about volunteering</a> · <a href="/contact/">Contact us</a> · <a href="/donate/">Support the farm</a></p>
';

as_olivia_upsert(
	'our-farm',
	'Our farm',
	as_olivia_banner(
		'Pea Ridge Road · Western Albemarle',
		'Our home—where the farm is the program.',
		'Roughly 85 acres of trails, animals, gardens, and activity stations, woven into the Blue Ridge Mountains. Licensed supports are integrated with hands-on skill-building, meaningful engagement, and a strong sense of purpose.'
	) . as_olivia_prose($farm_body)
);

// ---------------------------------------------------------------------------
// People
// ---------------------------------------------------------------------------
$people_body = '
<p>Meet the volunteer board and staff leaders who guide Autism Sanctuary’s mission, programs, and daily operations.</p>
<figure>' . as_olivia_placeholder('Photo placeholder: team and board') . '</figure>

<h2>Board of Directors</h2>
<div class="as-people">
  <div class="as-person">
    <div class="as-person__photo" aria-hidden="true">JB</div>
    <div>
      <h3>Jason Brewster</h3>
      <p class="as-person__role">President / Founder</p>
      <p>Parent of an autistic adult with high support needs and founder of Autism Sanctuary (2020). Entrepreneur and Director of Venture Programming at the University of Virginia’s Darden School of Business. Under his leadership, Autism Sanctuary grew into a DBHDS-licensed provider and received the 2025 Charlottesville Business Innovation Council Social Impact Award.</p>
    </div>
  </div>
  <div class="as-person">
    <div class="as-person__photo" aria-hidden="true">RK</div>
    <div>
      <h3>Robert Kreps</h3>
      <p class="as-person__role">Treasurer</p>
      <p>Parent of an autistic adult, retired engineer, and volunteer president of the Charlottesville Regional Autism Advocacy Group (CRAAG).</p>
    </div>
  </div>
  <div class="as-person">
    <div class="as-person__photo" aria-hidden="true">MO</div>
    <div>
      <h3>Matthew Osborne</h3>
      <p class="as-person__role">Secretary</p>
      <p>Director of Adult and Residential Services at the Faison Center. Psychologist and Licensed Board Certified Behavior Analyst (BCBA).</p>
    </div>
  </div>
  <div class="as-person">
    <div class="as-person__photo" aria-hidden="true">RN</div>
    <div>
      <h3>Rose Neville</h3>
      <p class="as-person__role">Board member</p>
      <p>Research Assistant Professor of Education and Director of the UVA Autism Research Core. Intellectual and developmental disabilities psychologist; licensed clinical psychologist in Virginia and board certified behavior analyst (BCBA-D).</p>
    </div>
  </div>
</div>

<h2>Management</h2>
<div class="as-people">
  <div class="as-person">
    <div class="as-person__photo" aria-hidden="true">OB</div>
    <div>
      <h3>Olivia Bruno</h3>
      <p class="as-person__role">Executive Director</p>
      <p>Olivia leads Autism Sanctuary’s therapeutic programming and day-to-day operations, shaping a welcoming, nature-based environment where participants feel empowered and included. She has taken on operational and financial responsibilities as the organization scaled from serving a handful of individuals to a growing regional provider.</p>
    </div>
  </div>
  <div class="as-person">
    <div class="as-person__photo" aria-hidden="true">IK</div>
    <div>
      <h3>Isabelle (Izzy) Kueser</h3>
      <p class="as-person__role">Director of Adult Services</p>
      <p>Biography coming soon.</p>
    </div>
  </div>
</div>
';

as_olivia_upsert(
	'people',
	'People',
	as_olivia_banner(
		'People',
		'The board and team who steward Autism Sanctuary.',
		'Governance and day-to-day leadership for our nonprofit care farm.'
	) . as_olivia_prose($people_body)
);

// ---------------------------------------------------------------------------
// Resources (no CSA; no Admissions link)
// ---------------------------------------------------------------------------
$resources_body = '
<h2>Referral &amp; application pathway</h2>
<ol>
  <li><strong>Review services:</strong> Read <a href="/programs/">Programs</a>.</li>
  <li><strong>Start a conversation:</strong> Email <a href="mailto:info@autismsanctuary.org">info@autismsanctuary.org</a> or call <a href="tel:+14342072118">(434) 207-2118</a>.</li>
  <li><strong>Share interest:</strong> Use the interest form on <a href="/programs/#interest">Programs</a>.</li>
  <li><strong>Plan authorization:</strong> We coordinate with individuals, families, and case managers to confirm funding and service alignment.</li>
</ol>

<h2>Waivers and individualized authorizations</h2>
<h3>Medicaid waivers (overview)</h3>
<p>Most people access our services through Virginia’s Medicaid waiver programs—commonly Family &amp; Individual Supports (FIS), Building Independence (BI), and Community Living (CL). Each has distinct eligibility rules, budgets, and service definitions.</p>
<p>The individual, their family, and their case manager determine whether a specific Autism Sanctuary service can be authorized on an ISP. Private-pay arrangements are also available when that path is the right fit.</p>

<h2>Accessibility &amp; transportation</h2>
<p>Inclusive access within a working farm.</p>
<p>Trails, gardens, and program buildings are checked for safe use; individualized plans may include adaptive equipment, pacing, or alternative activities when weather or terrain shifts.</p>
<p>Transportation arrangements vary; talk with our team about how people typically arrive and what supports are realistic for your situation.</p>
<p><a href="/programs/#interest">Explore a program fit</a> · <a href="/contact/?intent=program-fit">Send an inquiry</a></p>
';

as_olivia_upsert(
	'resources',
	'Resources',
	as_olivia_banner(
		'Resources &amp; guidance',
		'Funding, referrals, and getting here—without the jargon storm.',
		'Informational pathways for families and case managers. Not legal advice.'
	) . as_olivia_prose($resources_body)
);

// ---------------------------------------------------------------------------
// Careers
// ---------------------------------------------------------------------------
$careers_body = '
<figure>' . as_olivia_placeholder('Photo placeholder: DSP team and farm work') . '</figure>
<h2>Direct Support Professionals</h2>
<p>Be the teammate who makes a difference.</p>
<p>DSPs provide hands-on and person-centered support that helps individuals build skills, pursue their goals, and engage meaningfully in their communities and daily lives.</p>
<ul>
  <li><strong>Training provided:</strong> All required DBHDS, autism, and behavioral training is provided upon hire. No prior experience required.</li>
  <li><strong>Career pathways:</strong> Build experience that can translate to careers in nursing, occupational therapy, social work, education, and related fields.</li>
  <li><strong>Flexible hours and schedules:</strong> Find a service and schedule that works for you, with opportunities to fit this meaningful work into your life.</li>
  <li><strong>Supportive, team-oriented culture:</strong> Work alongside a team that values collaboration, compassion, and learning.</li>
  <li><strong>Meaningful, rewarding work:</strong> Build genuine relationships, make a difference, and see the impact of your work every day.</li>
</ul>
<figure>' . as_olivia_placeholder('Photo placeholder: careers and community') . '</figure>

<h2>Volunteers</h2>
<p>Help with trail maintenance, agriculture in the high tunnel and gardens, or—with training—assist staff in the adult day program. Roles are informal and complement paid staffing.</p>
<p><a href="/our-farm/">Learn about the farm</a></p>
<p>Use the inquiry form below and select <strong>Job opportunities</strong> or <strong>Volunteering</strong> so we can route your note.</p>
' . $gf_inquiry;

as_olivia_upsert(
	'careers',
	'Careers & volunteers',
	as_olivia_banner(
		'Join our community',
		'Make a Difference. Build Relationships. Love Where You Work!',
		'We hire Direct Support Professionals who are passionate about working with people with developmental disabilities and eager to grow and learn alongside the people we support!'
	) . as_olivia_prose($careers_body)
);

// ---------------------------------------------------------------------------
// Donate
// ---------------------------------------------------------------------------
$donate_body = '
<p>Your gift strengthens trails, barns, gardens, sensory spaces, staff development, and the programs that make Autism Sanctuary possible—alongside authorized services and private-pay arrangements where families choose that path.</p>
<div class="as-grid as-grid--3" style="margin:2rem 0">
  <div class="as-feature"><h3>Care farming infrastructure</h3><p>Trails, barns, gardens, and sensory spaces that make outdoor days possible.</p></div>
  <div class="as-feature"><h3>Participant access</h3><p>Support that helps people with significant needs take part fully in farm and community life.</p></div>
  <div class="as-feature"><h3>Workforce strength</h3><p>Training and retention for DSPs who show up in boots, rain or shine.</p></div>
</div>
<h2>Looking ahead</h2>
<ul class="as-checklist">
  <li><strong>Activity Barn</strong> purpose-built program space for growth and better day-program facilities.</li>
  <li><strong>Facility upgrades</strong> strengthening existing campus resources.</li>
  <li><strong>Workplace opportunities under development</strong> expanding vocational pathways for the people we support.</li>
</ul>
<figure>' . as_olivia_placeholder('Photo placeholder: giving impact') . '</figure>
' . ($gf_donate ? $gf_donate : '<p>Share your gift preference by emailing <a href="mailto:info@autismsanctuary.org">info@autismsanctuary.org</a> or calling <a href="tel:+14342072118">(434) 207-2118</a>.</p>');

as_olivia_upsert(
	'donate',
	'Donate',
	as_olivia_banner(
		'Philanthropy',
		'Help us keep farm days generous and joyful.',
		'Give in a way that fits—and tell us how you would like your gift used.'
	) . as_olivia_prose($donate_body)
);

// ---------------------------------------------------------------------------
// Soft-remove Admissions + Fellowship; set redirects
// ---------------------------------------------------------------------------
foreach (['admissions' => '/programs/#interest', 'fellowship' => '/careers/'] as $slug => $target) {
	$page = get_page_by_path($slug);
	if ($page) {
		wp_update_post([
			'ID'          => $page->ID,
			'post_status' => 'draft',
		]);
		as_olivia_log("Drafted /{$slug}/ (#{$page->ID}) → redirect {$target}");
	}
}

update_option('as_path_redirects', [
	'admissions' => home_url('/programs/#interest'),
	'fellowship' => home_url('/careers/'),
]);
as_olivia_log('Saved as_path_redirects option');

// ---------------------------------------------------------------------------
// Menus — keep existing Primary/Footer; only remove retired links
// ---------------------------------------------------------------------------
function as_olivia_menu_id_by_name($name) {
	foreach (wp_get_nav_menus() as $m) {
		if ($m->name === $name) {
			return (int) $m->term_id;
		}
	}
	return 0;
}

function as_olivia_prune_menu_items($menu_id, $retire_slugs) {
	if (!$menu_id) {
		return;
	}
	$items = wp_get_nav_menu_items($menu_id);
	if (!$items) {
		return;
	}
	foreach ($items as $item) {
		$path = trim((string) parse_url($item->url, PHP_URL_PATH), '/');
		$slug = strtolower(basename($path));
		$title = strtolower(trim($item->title));
		$hit = in_array($slug, $retire_slugs, true)
			|| in_array($title, $retire_slugs, true)
			|| ($item->object === 'page' && in_array($slug, $retire_slugs, true));
		if ($hit) {
			wp_delete_post($item->ID, true);
			as_olivia_log("Removed menu item #{$item->ID} ({$item->title}) from menu {$menu_id}");
		}
	}
}

$retire = ['admissions', 'fellowship', 'siemers fellowship'];
$primary_id = as_olivia_menu_id_by_name('Primary');
$footer_id = as_olivia_menu_id_by_name('Footer');
as_olivia_prune_menu_items($primary_id, $retire);
as_olivia_prune_menu_items($footer_id, $retire);

// Preserve existing location assignments; never assign Footer to secondary-menu
// (that duplicated the footer links in the header).
$locations = get_theme_mod('nav_menu_locations', []);
if (!is_array($locations)) {
	$locations = [];
}
if ($primary_id) {
	$locations['primary-menu'] = $primary_id;
}
if ($footer_id) {
	$locations['footer-menu'] = $footer_id;
}
if (!empty($locations['secondary-menu']) && !empty($footer_id) && (int) $locations['secondary-menu'] === (int) $footer_id) {
	unset($locations['secondary-menu']);
}
set_theme_mod('nav_menu_locations', $locations);
as_olivia_log('Menus pruned (Admissions/Fellowship removed); locations left intact without secondary duplicate');

// ---------------------------------------------------------------------------
// News banner stray-n cleanup if present as plain HTML
// ---------------------------------------------------------------------------
$news = get_page_by_path('news');
if ($news) {
	$c = $news->post_content;
	$cleaned = preg_replace('/>\s*n\s*</', '><', $c);
	$cleaned = str_replace(["\nn\n", '>n<', '>n <'], ["\n", '><', '><'], $cleaned);
	if ($cleaned !== $c) {
		wp_update_post(['ID' => $news->ID, 'post_content' => $cleaned]);
		as_olivia_log('Cleaned stray n artifacts on /news/');
	} else {
		as_olivia_log('News page checked (no stray-n pattern found in content)');
	}
}

as_olivia_log('=== Done ===');
