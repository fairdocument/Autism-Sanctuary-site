<?php
/**
 * Rebuild single-post Theme Builder body with a narrow forest header.
 * Run: wp eval-file wordpress-migration/polish-news-article-header.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

$admins = get_users(['role' => 'administrator', 'number' => 1]);
if ($admins) {
	wp_set_current_user((int) $admins[0]->ID);
}

if (!function_exists('et_theme_builder_insert_layout') || !function_exists('et_theme_builder_store_template')) {
	echo "Theme Builder APIs unavailable\n";
	return;
}
if (!current_user_can('edit_others_posts')) {
	echo "No admin capability for Theme Builder\n";
	return;
}

$json_flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

$eyebrow_html = '<p class="as-eyebrow">News</p>';
$eyebrow_attrs = wp_json_encode([
	'builderVersion' => '4.27.4',
	'modulePreset'   => ['default'],
	'module'         => [
		'advanced' => [
			'htmlAttributes' => [
				'desktop' => ['value' => ['class' => 'as-single-eyebrow']],
			],
		],
	],
	'content'        => [
		'innerContent' => [
			'desktop' => ['value' => $eyebrow_html],
		],
	],
], $json_flags);

$back_html = '<p><a class="as-btn as-btn--ghost" href="/news/">&larr; All news</a></p>';
$back_attrs = wp_json_encode([
	'builderVersion' => '4.27.4',
	'modulePreset'   => ['default'],
	'module'         => [
		'advanced'   => [
			'htmlAttributes' => [
				'desktop' => ['value' => ['class' => 'as-single-back']],
			],
		],
		'decoration' => [
			'spacing' => [
				'desktop' => [
					'value' => [
						'margin' => [
							'top'             => '2.5rem',
							'right'           => '',
							'bottom'          => '',
							'left'            => '',
							'syncVertical'    => 'off',
							'syncHorizontal'  => 'off',
						],
					],
				],
			],
		],
	],
	'content'        => [
		'innerContent' => [
			'desktop' => ['value' => $back_html],
		],
	],
], $json_flags);

$body = <<<BLK
<!-- wp:divi/section {"builderVersion":"5.0.0-public-alpha.18.2","modulePreset":["default"],"module":{"decoration":{"spacing":{"desktop":{"value":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px","syncVertical":"off","syncHorizontal":"off"}}}},"background":{"desktop":{"value":{"color":"#1E3D2C"}}},"layout":{"desktop":{"value":{"display":"block"}}}},"advanced":{"htmlAttributes":{"desktop":{"value":{"class":"as-single-banner"}}}}}} -->
<!-- wp:divi/row {"builderVersion":"5.0.0-public-alpha.18.2","modulePreset":["default"],"module":{"decoration":{"sizing":{"desktop":{"value":{"width":"100%","maxWidth":"80rem"}}},"spacing":{"desktop":{"value":{"padding":{"top":"2rem","right":"1.25rem","bottom":"1.75rem","left":"1.25rem","syncVertical":"off","syncHorizontal":"off"}}}},"layout":{"desktop":{"value":{"display":"block"}}}}}} -->
<!-- wp:divi/column {"module":{"advanced":{"type":{"desktop":{"value":"4_4"}}},"decoration":{"layout":{"desktop":{"value":{"display":"block"}}}}},"builderVersion":"5.0.0-public-alpha.18.2","modulePreset":["default"]} -->
<!-- wp:divi/text {$eyebrow_attrs} -->
<!-- /wp:divi/text -->
<!-- wp:divi/post-title {"title":{"advanced":{"showTitle":{"desktop":{"value":"on"}}},"decoration":{"font":{"font":{"desktop":{"value":{"headingLevel":"h1","family":"Cormorant Garamond","weight":"700","size":"clamp(1.75rem, 3.5vw, 2.35rem)","color":"#FFFFFF"}}}}}},"meta":{"advanced":{"showMeta":{"desktop":{"value":"on"}},"showAuthor":{"desktop":{"value":"on"}},"showDate":{"desktop":{"value":"on"}},"showCategories":{"desktop":{"value":"off"}},"showCommentsCount":{"desktop":{"value":"off"}}},"decoration":{"font":{"font":{"desktop":{"value":{"family":"Source Sans 3","weight":"400","color":"#F7F4EC","size":"0.95rem"}}}}}},"image":{"advanced":{"enabled":{"desktop":{"value":"off"}}}},"builderVersion":"4.27.4","modulePreset":["default"],"module":{"advanced":{"htmlAttributes":{"desktop":{"value":{"class":"as-single-title"}}}},"decoration":{"spacing":{"desktop":{"value":{"margin":{"top":"0px","right":"","bottom":"0px","left":"","syncVertical":"off","syncHorizontal":"off"}}}}}}} -->
<!-- /wp:divi/post-title -->
<!-- /wp:divi/column -->
<!-- /wp:divi/row -->
<!-- /wp:divi/section -->
<!-- wp:divi/section {"builderVersion":"5.0.0-public-alpha.18.2","modulePreset":["default"],"module":{"decoration":{"spacing":{"desktop":{"value":{"padding":{"top":"2.5rem","right":"0px","bottom":"4.5rem","left":"0px","syncVertical":"off","syncHorizontal":"off"}}}},"background":{"desktop":{"value":{"color":"RGBA(255,255,255,0)"}}},"layout":{"desktop":{"value":{"display":"block"}}}},"advanced":{"htmlAttributes":{"desktop":{"value":{"class":"as-single-post"}}}}}} -->
<!-- wp:divi/row {"builderVersion":"5.0.0-public-alpha.18.2","modulePreset":["default"],"module":{"decoration":{"sizing":{"desktop":{"value":{"maxWidth":"44rem"}}},"spacing":{"desktop":{"value":{"padding":{"top":"0px","right":"1.25rem","bottom":"0px","left":"1.25rem","syncVertical":"off","syncHorizontal":"off"}}}},"layout":{"desktop":{"value":{"display":"block"}}}}}} -->
<!-- wp:divi/column {"module":{"advanced":{"type":{"desktop":{"value":"4_4"}}},"decoration":{"layout":{"desktop":{"value":{"display":"block"}}}}},"builderVersion":"5.0.0-public-alpha.18.2","modulePreset":["default"]} -->
<!-- wp:divi/post-title {"title":{"advanced":{"showTitle":{"desktop":{"value":"off"}}}},"meta":{"advanced":{"showMeta":{"desktop":{"value":"off"}},"showAuthor":{"desktop":{"value":"off"}},"showDate":{"desktop":{"value":"off"}},"showCategories":{"desktop":{"value":"off"}},"showCommentsCount":{"desktop":{"value":"off"}}}},"image":{"advanced":{"enabled":{"desktop":{"value":"on"}}}},"builderVersion":"4.27.4","modulePreset":["default"],"module":{"advanced":{"htmlAttributes":{"desktop":{"value":{"class":"as-single-featured"}}}},"decoration":{"spacing":{"desktop":{"value":{"margin":{"top":"0px","right":"","bottom":"1.75rem","left":"","syncVertical":"off","syncHorizontal":"off"}}}}}}} -->
<!-- /wp:divi/post-title -->
<!-- wp:divi/post-content {"builderVersion":"4.27.4","modulePreset":["default"],"module":{"advanced":{"htmlAttributes":{"desktop":{"value":{"class":"as-single-content as-prose"}}}},"decoration":{"bodyFont":{"body":{"font":{"desktop":{"value":{"family":"Source Sans 3","weight":"400","color":"#4A534C"}}}}},"headingFont":{"h1":{"font":{"desktop":{"value":{"family":"Cormorant Garamond","weight":"400","color":"#1E3D2C"}}}}}}}} -->
<!-- /wp:divi/post-content -->
<!-- wp:divi/code {$back_attrs} /-->
<!-- /wp:divi/column -->
<!-- /wp:divi/row -->
<!-- /wp:divi/section -->
BLK;

$body_id = (int) get_option('as_tb_single_post_body_id');
if ($body_id && get_post_type($body_id) === ET_THEME_BUILDER_BODY_LAYOUT_POST_TYPE) {
	wp_update_post([
		'ID'           => $body_id,
		'post_title'   => 'AS Single Post Body',
		'post_content' => wp_slash($body),
	]);
	echo "Updated single-post body #{$body_id}\n";
} else {
	$body_id = et_theme_builder_insert_layout([
		'post_type'    => ET_THEME_BUILDER_BODY_LAYOUT_POST_TYPE,
		'post_title'   => 'AS Single Post Body',
		'post_content' => $body,
	]);
	if (is_wp_error($body_id)) {
		echo 'Body layout error: ' . $body_id->get_error_message() . "\n";
		return;
	}
	update_option('as_tb_single_post_body_id', (int) $body_id);
	echo "Created single-post body #{$body_id}\n";
}

update_post_meta($body_id, '_et_pb_use_builder', 'on');
update_post_meta($body_id, '_et_builder_version', '5.0.0');

$tb_id = et_theme_builder_get_theme_builder_post_id(true, true);
$template_id = (int) get_option('as_tb_single_post_template_id');
$template = [
	'id'                  => $template_id ?: 0,
	'title'               => 'AS All Posts',
	'default'             => '0',
	'enabled'             => '1',
	'autogenerated_title' => '0',
	'use_on'              => ['singular:post_type:post:all'],
	'exclude_from'        => [],
	'layouts'             => [
		'header' => ['id' => 0, 'enabled' => false],
		'body'   => ['id' => (int) $body_id, 'enabled' => true],
		'footer' => ['id' => 0, 'enabled' => false],
	],
];

$stored = et_theme_builder_store_template($tb_id, $template, false);
if ($stored) {
	update_option('as_tb_single_post_template_id', (int) $stored);
	$ids = et_theme_builder_get_theme_builder_template_ids(true, $tb_id);
	if (!in_array((int) $stored, $ids, true)) {
		add_post_meta($tb_id, '_et_template', (int) $stored);
	}
	echo "Theme Builder template #{$stored} assigned\n";
} else {
	echo "Failed to store Theme Builder template\n";
}

// Validate JSON modules.
$saved = get_post($body_id)->post_content;
foreach (['divi/text', 'divi/code'] as $mod) {
	if (preg_match('/<!-- wp:' . preg_quote($mod, '/') . ' (\{.*?\})(?: -->| \/-->)/s', $saved, $m)) {
		$ok = json_decode($m[1], true);
		echo $mod . ': ' . ($ok ? "JSON OK\n" : ("JSON FAIL " . json_last_error_msg() . "\n"));
	}
}

if (class_exists('ET_Core_PageResource')) {
	ET_Core_PageResource::remove_static_resources('all', 'all');
	echo "Cleared Divi static CSS\n";
}
if (function_exists('wp_cache_flush')) {
	wp_cache_flush();
}

echo "Done. Hard-refresh a news article.\n";
