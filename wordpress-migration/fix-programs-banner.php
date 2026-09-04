<?php
/**
 * Match /programs/ header formatting to /about/ (Text + Heading + Text, flex banner).
 *
 * Run: wp eval-file wordpress-migration/fix-programs-banner.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

$id = 27;
$content = get_post_field('post_content', $id);
$backup = WP_CONTENT_DIR . '/as-programs-banner-backup-' . gmdate('Ymd-His') . '.txt';
file_put_contents($backup, $content);
echo "Backup: {$backup}\n";

$eyebrow = 'Programs & licensed services';
$heading = 'Licensed support rooted in connection, growth, and belonging.';
$lede = 'Autism Sanctuary is a Virginia DBHDS-licensed provider serving adults with developmental disabilities—on the farm, in the community, and in people’s homes.';

$eyebrow_json = str_replace(['\\', '"'], ['\\\\', '\\"'], $eyebrow) . '\\n';
$heading_json = str_replace(['\\', '"'], ['\\\\', '\\"'], $heading);
$lede_json = str_replace(['\\', '"'], ['\\\\', '\\"'], $lede) . '\\n\\n';

$banner = <<<BLOCKS
<!-- wp:divi/placeholder --><!-- wp:divi/section {"module":{"decoration":{"background":{"desktop":{"value":{"color":"\$variable({\u0022type\u0022:\u0022color\u0022,\u0022value\u0022:{\u0022name\u0022:\u0022gcid-22sbvlqwkx\u0022,\u0022settings\u0022:{}}})$"}}},"spacing":{"desktop":{"value":{"padding":{"top":"80px","syncVertical":"off","syncHorizontal":"off","bottom":"40px"}}},"tablet":{"value":{"padding":{"syncVertical":"off","syncHorizontal":"on","left":"15px","right":"15px"}}}}}},"builderVersion":"5.11.1"} -->
<!-- wp:divi/row {"module":{"advanced":{"columnStructure":{"desktop":{"value":"4_4"}},"flexColumnStructure":{"desktop":{"value":"equal-columns_1"}}},"decoration":{"layout":{"desktop":{"value":{"flexWrap":"nowrap"}}},"sizing":{"desktop":{"value":{"width":"100%","maxWidth":"80rem"}}}}},"builderVersion":"5.11.1"} -->
<!-- wp:divi/column {"module":{"advanced":{"type":{"desktop":{"value":"4_4"}}},"decoration":{"sizing":{"desktop":{"value":{"flexType":"24_24"}}},"layout":{"desktop":{"value":{"rowGap":"15px"}}}}},"builderVersion":"5.11.1"} -->
<!-- wp:divi/text {"content":{"innerContent":{"desktop":{"value":"{$eyebrow_json}"}},"decoration":{"bodyFont":{"body":{"font":{"desktop":{"value":{"size":"16px","color":"#ffffff"}}}}}}},"builderVersion":"5.11.1"} /-->

<!-- wp:divi/heading {"module":{"decoration":{"sizing":{"desktop":{"value":{"width":"100%"}},"phone":{"value":{"width":"100%"}}}}},"title":{"innerContent":{"desktop":{"value":"{$heading_json}"}},"decoration":{"font":{"font":{"desktop":{"value":{"color":"#ffffff","size":"40px","weight":"700","weightFineTune":"","variationSettings":{"WGHT":""}}}}}}},"builderVersion":"5.11.1"} /-->

<!-- wp:divi/text {"content":{"innerContent":{"desktop":{"value":"{$lede_json}"}},"decoration":{"bodyFont":{"body":{"font":{"desktop":{"value":{"size":"16px","color":"#ffffff"}}}}}}},"builderVersion":"5.11.1"} /-->
<!-- /wp:divi/column -->
<!-- /wp:divi/row -->
<!-- /wp:divi/section -->
BLOCKS;

$pattern = '/^(?:<!-- wp:divi\/placeholder -->)?<!-- wp:divi\/section .*?<!-- \/wp:divi\/section -->/s';
if (!preg_match($pattern, $content)) {
	echo "FAIL: could not find first section\n";
	return;
}

$new = preg_replace($pattern, trim($banner), $content, 1);
if ($new === null || $new === $content) {
	echo "FAIL: replace did not change content\n";
	return;
}

wp_update_post([
	'ID'           => $id,
	'post_content' => wp_slash($new),
]);
update_post_meta($id, '_et_builder_version', '5.0.0');

$cache = WP_CONTENT_DIR . '/et-cache/' . $id;
if (is_dir($cache)) {
	$it = new RecursiveDirectoryIterator($cache, FilesystemIterator::SKIP_DOTS);
	$files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
	foreach ($files as $file) {
		$file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
	}
	@rmdir($cache);
}
if (function_exists('wp_cache_flush')) {
	wp_cache_flush();
}

$saved = get_post_field('post_content', $id);
echo (strpos($saved, 'wp:divi/heading') !== false) ? "OK has heading module\n" : "FAIL missing heading\n";
echo (strpos($saved, 'as-programs-banner') === false) ? "OK old banner class removed\n" : "WARN still has as-programs-banner\n";
echo "=== Done ===\n";
