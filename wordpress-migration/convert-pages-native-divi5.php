<?php
/**
 * Convert marketing pages from HTML-in-Code to native Divi 5 Text/Image modules.
 * Skips /about/ (hand-tuned pilot). Fixes common copy typos during migration.
 *
 * Run: wp eval-file wordpress-migration/convert-pages-native-divi5.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

if (!class_exists('\\ET\\Builder\\Packages\\Conversion\\Conversion')) {
	echo "Divi 5 Conversion API not available.\n";
	return;
}

require_once __DIR__ . '/native-divi-lib.php';

$admins = get_users(['role' => 'administrator', 'number' => 1]);
if ($admins) {
	wp_set_current_user((int) $admins[0]->ID);
}

\ET\Builder\Packages\Conversion\Conversion::initialize_shortcode_framework();

$skip = ['about'];
$backup_dir = WP_CONTENT_DIR . '/as-native-divi-backup-' . gmdate('Ymd-His');
wp_mkdir_p($backup_dir);

echo "=== Convert pages → native Divi 5 ===\n";
echo "Backup: {$backup_dir}\n";

$pages = get_posts([
	'post_type'      => 'page',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'orderby'        => 'menu_order title',
	'order'          => 'ASC',
]);

foreach ($pages as $page) {
	if (in_array($page->post_name, $skip, true)) {
		echo "Skip /{$page->post_name}/ (pilot page)\n";
		continue;
	}
	$append_blog = ($page->post_name === 'news');
	echo as_native_convert_page($page, $backup_dir, $append_blog);
}

if (function_exists('wp_cache_flush')) {
	wp_cache_flush();
}

echo "=== Done ===\n";
