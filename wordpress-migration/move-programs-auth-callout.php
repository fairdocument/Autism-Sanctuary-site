<?php
/**
 * Move Authorization paragraph on /programs/ into a callout above the footer.
 *
 * Run: wp eval-file wordpress-migration/move-programs-auth-callout.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

require_once __DIR__ . '/native-divi-lib.php';

if (!class_exists('\\ET\\Builder\\Packages\\Conversion\\Conversion')) {
	echo "Divi 5 Conversion API not available.\n";
	return;
}

$admins = get_users(['role' => 'administrator', 'number' => 1]);
if ($admins) {
	wp_set_current_user((int) $admins[0]->ID);
}
\ET\Builder\Packages\Conversion\Conversion::initialize_shortcode_framework();

$id = 27;
$content = get_post_field('post_content', $id);
$backup = WP_CONTENT_DIR . '/as-programs-auth-backup-' . gmdate('Ymd-His') . '.txt';
file_put_contents($backup, $content);
echo "Backup: {$backup}\n";

function as_auth_encode_divi($html) {
	return str_replace(
		['\\', '"', "\n", '<', '>', '&'],
		['\\\\', '\\"', '\\n', '\\u003c', '\\u003e', '\\u0026'],
		$html
	);
}

$intro_html = <<<'HTML'
<h2>Licensed service lines</h2>
<p>Autism Sanctuary is a Virginia DBHDS-licensed provider serving adults with developmental disabilities. Licensed services show up both alongside our farming operations as well as out in the community and in people’s homes, creating a more integrated support structure tailored to each individual’s needs.</p>
<p>A range of support ratios is provided based on individual support needs.</p>
HTML;

$callout_html = <<<'HTML'
<div class="as-callout as-callout--auth">
<p><strong>Authorization and eligibility:</strong> Service availability, funding, and authorization vary by person and payer. Many people access services through Virginia’s Medicaid waiver programs; private-pay arrangements are also available. See <a href="/resources/">Resources</a> or the interest form above.</p>
</div>
HTML;

$needle = 'Authorization and eligibility';
$pos = strpos($content, $needle);
if ($pos !== false) {
	// Only rewrite intro if Authorization is still in the early intro module.
	$value_key = '"innerContent":{"desktop":{"value":"';
	$vstart = strrpos(substr($content, 0, $pos), $value_key);
	if ($vstart !== false) {
		$val_begin = $vstart + strlen($value_key);
		$after = substr($content, $val_begin);
		if (preg_match('/^(.*?)"\}\}/s', $after, $vm)) {
			$old_val = $vm[1];
			if (strpos($old_val, 'Licensed') !== false) {
				$content = substr($content, 0, $val_begin) . as_auth_encode_divi($intro_html) . substr($content, $val_begin + strlen($old_val));
				echo "OK stripped Authorization from intro\n";
			}
		}
	}
}

// Remove any existing auth callout sections.
$content = preg_replace(
	'/<!-- wp:divi\/section(?:\s+\{.*?\})? -->\s*<!-- wp:divi\/row[\s\S]*?(?:as-programs-auth-callout|as-programs-auth-copy|as-callout--auth)[\s\S]*?<!-- \/wp:divi\/section -->\s*/',
	'',
	$content
);

$text_style = as_native_text_style();
$sc = sprintf(
	'[et_pb_section fb_built="1" _builder_version="4.27.4" _module_preset="default" custom_padding="0px|0px|4.5rem|0px|false|false" background_color="RGBA(255,255,255,0)" module_class="as-native-prose as-programs-auth-callout"][et_pb_row _builder_version="4.27.4" _module_preset="default" custom_padding="0px|1.25rem|0px|1.25rem|false|false" max_width="44rem"][et_pb_column type="4_4" _builder_version="4.27.4" _module_preset="default"][et_pb_text _builder_version="4.27.4" _module_preset="default" module_class="as-prose as-programs-auth-copy" %1$s]%2$s[/et_pb_text][/et_pb_column][/et_pb_row][/et_pb_section]',
	$text_style,
	$callout_html
);

$converted = \ET\Builder\Packages\Conversion\Conversion::maybeConvertContent($sc, true, $id, true);
if (!$converted || strpos($converted, 'wp:divi/') === false) {
	echo "FAIL convert callout\n";
	return;
}

$content = rtrim($content) . "\n\n" . trim($converted) . "\n";

wp_update_post([
	'ID'           => $id,
	'post_content' => wp_slash($content),
]);

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
$auth_count = substr_count($saved, 'Authorization and eligibility');
echo "auth mentions={$auth_count}\n";
echo (strpos($saved, 'as-programs-auth-callout') !== false || strpos($saved, 'auth\\u002dcallout') !== false)
	? "OK callout section\n"
	: "FAIL no callout section\n";
echo ($auth_count === 1) ? "OK single auth copy\n" : "WARN auth count {$auth_count}\n";
echo "=== Done ===\n";
