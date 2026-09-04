<?php
/**
 * Plugin Name: Autism Sanctuary Google Analytics
 * Description: GA4 Google tag matching live Site Kit (measurement G-Z2VYQCYE23 via GT-P8Z4CWCX).
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Live Site Kit settings (autismsanctuary public_html):
 * - measurementID: G-Z2VYQCYE23
 * - googleTagID: GT-P8Z4CWCX
 * - googleTagContainerDestinationIDs: [G-Z2VYQCYE23]
 * - trackingDisabled: [loggedinUsers]
 * - useSnippet: true
 * - linker domains: archive.autismsanctuary.org
 */
add_action('wp_head', function () {
	if (is_admin()) {
		return;
	}

	// Match Site Kit: do not track logged-in users.
	if (is_user_logged_in()) {
		return;
	}

	$tag_id = 'GT-P8Z4CWCX';
	$linker_domains = ['archive.autismsanctuary.org'];
	?>
<!-- Google tag (gtag.js) — same property as live autismsanctuary.org -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($tag_id); ?>"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('set', 'linker', {'domains': <?php echo wp_json_encode($linker_domains); ?>});
gtag('js', new Date());
gtag('config', <?php echo wp_json_encode($tag_id); ?>);
</script>
	<?php
}, 1);
