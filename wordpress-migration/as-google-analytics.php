<?php
/**
 * Plugin Name: Autism Sanctuary Google Analytics
 * Description: GA4 Google tag matching live Site Kit (measurement G-Z2VYQCYE23 via GT-P8Z4CWCX).
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Fallback gtag while Site Kit is not yet connected / not outputting its snippet.
 *
 * Target property (from archive Site Kit):
 * - measurementID: G-Z2VYQCYE23
 * - googleTagID: GT-P8Z4CWCX
 * - trackingDisabled: loggedinUsers
 * - linker: archive.autismsanctuary.org
 */
add_action('wp_head', function () {
	if (is_admin()) {
		return;
	}

	// Defer to Site Kit once Analytics is connected and its snippet is enabled.
	if (as_site_kit_owns_analytics_snippet()) {
		return;
	}

	// Match Site Kit: do not track logged-in users.
	if (is_user_logged_in()) {
		return;
	}

	$tag_id = 'GT-P8Z4CWCX';
	$linker_domains = ['archive.autismsanctuary.org'];
	?>
<!-- Google tag (gtag.js) — same property as archive Site Kit -->
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

/**
 * True when Google Site Kit Analytics-4 is connected and will print the gtag snippet.
 */
function as_site_kit_owns_analytics_snippet(): bool {
	if (!function_exists('is_plugin_active')) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	if (!is_plugin_active('google-site-kit/google-site-kit.php')) {
		return false;
	}

	$modules = get_option('googlesitekit_active_modules', []);
	if (!is_array($modules) || !in_array('analytics-4', $modules, true)) {
		return false;
	}

	$settings = get_option('googlesitekit_analytics-4_settings', []);
	if (!is_array($settings)) {
		return false;
	}

	$measurement = $settings['measurementID'] ?? '';
	$use_snippet = !empty($settings['useSnippet']);
	$connected = get_option('googlesitekit_has_connected_admins');

	return $use_snippet && $measurement !== '' && !empty($connected);
}
