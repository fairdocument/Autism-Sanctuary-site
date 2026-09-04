<?php
/**
 * Apply archived Site Kit Analytics + Search Console targets after OAuth connect.
 * Run: wp eval-file wordpress-migration/setup-site-kit.php
 *
 * Does nothing until googlesitekit_has_connected_admins is set (Sign in with Google done).
 * Prefer selecting the existing property in the Site Kit UI; this only fills gaps / aligns options.
 */

if (!defined('ABSPATH')) {
	exit(1);
}

$connected = get_option('googlesitekit_has_connected_admins');
if (empty($connected)) {
	echo "Site Kit is not connected yet.\n";
	echo "1. Log into WP admin\n";
	echo "2. Open " . home_url('/wp-admin/admin.php?page=googlesitekit-splash') . "\n";
	echo "3. Sign in with Google (same account as archive Site Kit)\n";
	echo "4. Connect Analytics property G-Z2VYQCYE23 (property 497349396)\n";
	echo "5. Connect Search Console sc-domain:autismsanctuary.org\n";
	echo "6. Re-run this script if needed\n";
	return;
}

$analytics = get_option('googlesitekit_analytics-4_settings', []);
if (!is_array($analytics)) {
	$analytics = [];
}

$targets = [
	'accountID' => '361954890',
	'propertyID' => '497349396',
	'webDataStreamID' => '11503992433',
	'measurementID' => 'G-Z2VYQCYE23',
	'googleTagID' => 'GT-P8Z4CWCX',
	'googleTagAccountID' => '6304477592',
	'googleTagContainerID' => '225231625',
	'googleTagContainerDestinationIDs' => ['G-Z2VYQCYE23'],
	'trackingDisabled' => ['loggedinUsers'],
	'useSnippet' => true,
];

foreach ($targets as $key => $value) {
	$analytics[$key] = $value;
}
if (empty($analytics['ownerID'])) {
	$analytics['ownerID'] = 1;
}

update_option('googlesitekit_analytics-4_settings', $analytics);

$modules = get_option('googlesitekit_active_modules', []);
if (!is_array($modules)) {
	$modules = [];
}
foreach (['analytics-4', 'pagespeed-insights', 'search-console'] as $mod) {
	if (!in_array($mod, $modules, true)) {
		$modules[] = $mod;
	}
}
update_option('googlesitekit_active_modules', $modules);

$sc = get_option('googlesitekit_search-console_settings', []);
if (!is_array($sc)) {
	$sc = [];
}
$sc['propertyID'] = 'sc-domain:autismsanctuary.org';
if (empty($sc['ownerID'])) {
	$sc['ownerID'] = 1;
}
update_option('googlesitekit_search-console_settings', $sc);

echo "Updated Site Kit Analytics + Search Console options to match archive.\n";
echo "measurementID=" . $analytics['measurementID'] . " googleTagID=" . $analytics['googleTagID'] . "\n";
echo "searchConsole=" . $sc['propertyID'] . "\n";
echo "active_modules=" . implode(',', $modules) . "\n";
