<?php
/**
 * Repair Theme Builder single-post back link (JSON-safe Code module).
 * Run: wp eval-file wordpress-migration/fix-news-tb-back.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

$bid = (int) get_option('as_tb_single_post_body_id');
$post = get_post($bid);
if (!$post) {
	echo "Missing TB body\n";
	return;
}

$html = '<p><a class="as-btn as-btn--ghost" href="/news/">&larr; All news</a></p>';

$code_attrs = [
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
			'desktop' => ['value' => $html],
		],
	],
];

$attrs_json = wp_json_encode($code_attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if (!$attrs_json) {
	echo "Failed to encode code attrs\n";
	return;
}

$content = $post->post_content;
// Drop existing code module + closing column/row/section so we can re-append cleanly.
$content = preg_replace('/<!-- wp:divi\/code\b.*?<!-- \/wp:divi\/code -->/s', '', $content);
$content = preg_replace('/<!-- wp:divi\/code\b.*?\/-->/s', '', $content);
$content = preg_replace('/<!-- \/wp:divi\/column -->.*$/s', '', $content);
$content = rtrim($content) . "\n<!-- wp:divi/code {$attrs_json} /-->\n<!-- /wp:divi/column --><!-- /wp:divi/row --><!-- /wp:divi/section -->";

// wp_update_post expects slashed data; JSON backslashes must survive.
wp_update_post([
	'ID'           => $bid,
	'post_content' => wp_slash($content),
]);
update_post_meta($bid, '_et_pb_use_builder', 'on');

$saved = get_post($bid)->post_content;
if (!preg_match('/<!-- wp:divi\/code (.*?) \/-->/s', $saved, $m)) {
	echo "Code module missing after save\n";
	return;
}
$decoded = json_decode($m[1], true);
if (!$decoded) {
	echo "JSON still invalid: " . json_last_error_msg() . "\n";
	echo substr($m[1], 0, 400) . "\n";
	return;
}
echo "OK back link value: " . $decoded['content']['innerContent']['desktop']['value'] . "\n";
