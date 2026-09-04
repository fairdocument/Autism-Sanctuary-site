<?php
/**
 * Configure Gravity SMTP with SendGrid using the live site's GF SendGrid API key.
 * Run: wp eval-file wordpress-migration/setup-gravity-smtp-sendgrid.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

$live_settings = null;
$live_path = '/home/sites/autismsanctuary/public_html/wp-content/plugins/gravityformssendgrid';

// Prefer reading the live WP option via a direct DB lookup if available.
global $wpdb;

// Try loading key from sibling live site options table if we can resolve it.
$api_key = '';
$live_option = null;

// Read from live site via WP-CLI subprocess is more reliable; allow env override.
if (defined('AS_SENDGRID_API_KEY') && AS_SENDGRID_API_KEY) {
	$api_key = AS_SENDGRID_API_KEY;
}

if ($api_key === '' && isset($GLOBALS['as_sendgrid_api_key'])) {
	$api_key = (string) $GLOBALS['as_sendgrid_api_key'];
}

if ($api_key === '') {
	echo "No API key provided. Pass via \$GLOBALS['as_sendgrid_api_key'].\n";
	return;
}

$from_email = 'info@autismsanctuary.org';
$from_name  = 'Autism Sanctuary';

$sendgrid = [
	'api_key'                => $api_key,
	'region'                 => 'global',
	'ip_pool_name'           => '',
	'from_email'             => $from_email,
	'from_name'              => $from_name,
	'force_from_email'       => false,
	'force_from_name'        => false,
	'reply_to_email'         => $from_email,
	'force_reply_to_email'   => false,
	'enabled'                => true,
	'activated'              => true,
	'configured'             => true,
	'is_primary'             => true,
	'is_backup'              => false,
];

update_option('gravitysmtp_sendgrid', wp_json_encode($sendgrid));

$config = get_option('gravitysmtp_config', '{}');
$config = is_string($config) ? json_decode($config, true) : $config;
if (!is_array($config)) {
	$config = [];
}

$config['test_mode'] = false;
$config['event_log_enabled'] = true;
$config['event_log_retention'] = $config['event_log_retention'] ?? 180;
$config['enabled_connector'] = ['sendgrid' => true];
$config['primary_connector'] = ['sendgrid' => true];
$config['backup_connector']  = ['sendgrid' => false];

update_option('gravitysmtp_config', wp_json_encode($config));
delete_transient('gsmtp_connector_configured_sendgrid');

echo "Saved gravitysmtp_sendgrid + gravitysmtp_config\n";

// Verify key against SendGrid.
$response = wp_remote_get('https://api.sendgrid.com/v3/scopes', [
	'headers' => [
		'Authorization' => 'Bearer ' . $api_key,
		'Content-Type'  => 'application/json',
	],
	'timeout' => 20,
]);
$code = (int) wp_remote_retrieve_response_code($response);
echo "SendGrid scopes HTTP={$code}\n";
if ($code < 200 || $code > 299) {
	echo 'Body: ' . substr((string) wp_remote_retrieve_body($response), 0, 300) . "\n";
	echo "ABORT: API key invalid; leaving settings saved for inspection.\n";
	return;
}

// Send a test message through wp_mail (should route via Gravity SMTP → SendGrid).
$to = 'brewster.jason@gmail.com';
$subject = 'Autism Sanctuary mail test — Gravity SMTP SendGrid';
$body = '<p>This is a test from the new site confirming Gravity SMTP is sending through SendGrid.</p><p>From: ' . esc_html($from_email) . '</p>';
$headers = [
	'Content-Type: text/html; charset=UTF-8',
	'From: ' . $from_name . ' <' . $from_email . '>',
	'Reply-To: ' . $from_email,
	'Cc: olivia@autismsanctuary.org',
];

$ok = wp_mail($to, $subject, $body, $headers);
echo $ok ? "wp_mail returned true\n" : "wp_mail returned false\n";

global $wpdb;
$row = $wpdb->get_row("SELECT id, status, service, subject, date_updated FROM {$wpdb->prefix}gravitysmtp_events ORDER BY id DESC LIMIT 1");
if ($row) {
	echo "latest_event #{$row->id} status={$row->status} service={$row->service} subject={$row->subject}\n";
	$log = $wpdb->get_results($wpdb->prepare(
		"SELECT action_name, log_value FROM {$wpdb->prefix}gravitysmtp_event_logs WHERE event_id=%d ORDER BY id",
		$row->id
	));
	foreach ($log as $l) {
		echo "  log {$l->action_name}: " . substr($l->log_value, 0, 200) . "\n";
	}
}

echo "=== Done ===\n";
