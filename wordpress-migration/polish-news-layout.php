<?php
/**
 * Rebuild News page banner (About-style) + ensure blog thumbnails.
 * Run: wp eval-file wordpress-migration/polish-news-layout.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

$news = get_page_by_path('news');
if (!$news) {
	echo "News page missing\n";
	return;
}

update_option('page_for_posts', 0);

$banner_html = '<p class="as-eyebrow">Newsroom</p>'
	. '<h1>News &amp; updates</h1>'
	. '<p class="as-lede">Farm stories, program updates, and community notes from Autism Sanctuary.</p>';

$banner_text = [
	'builderVersion' => '4.27.4',
	'modulePreset'   => ['default'],
	'module'         => [
		'advanced' => [
			'htmlAttributes' => [
				'desktop' => ['value' => ['class' => 'as-news-banner']],
			],
		],
	],
	'content'        => [
		'innerContent' => [
			'desktop' => ['value' => $banner_html],
		],
	],
];

$blog = [
	'module' => [
		'advanced'   => [
			'htmlAttributes' => [
				'desktop' => ['value' => ['class' => '', 'id' => '']],
			],
		],
		'decoration' => [
			'spacing'    => [
				'desktop' => [
					'value' => [
						'padding' => [
							'top'            => '0px',
							'right'          => '0px',
							'bottom'         => '0px',
							'left'           => '0px',
							'syncVertical'   => 'off',
							'syncHorizontal' => 'off',
						],
					],
				],
			],
			'attributes' => [
				'desktop' => [
					'value' => [
						'attributes' => [
							[
								'id'         => 'as-news-blog-class',
								'name'       => 'class',
								'value'      => 'as-news-blog',
								'adminLabel' => 'CSS Class',
							],
						],
					],
				],
			],
		],
	],
	'post'   => [
		'advanced'   => [
			'number'         => ['desktop' => ['value' => '10']],
			'dateFormat'     => ['desktop' => ['value' => 'F j, Y']],
			'excerptContent' => ['desktop' => ['value' => 'off']],
			'showExcerpt'    => ['desktop' => ['value' => 'on']],
			'excerptLength'  => ['desktop' => ['value' => '28']],
		],
		'decoration' => [
			'border' => [
				'desktop' => [
					'value' => [
						'styles' => [
							'all' => ['width' => '0px'],
						],
					],
				],
			],
		],
	],
	'image'  => [
		'advanced' => [
			'enable' => ['desktop' => ['value' => 'on']],
		],
	],
	'header' => [
		'advanced'   => [
			'level' => ['desktop' => ['value' => 'h2']],
		],
		'decoration' => [
			'font' => [
				'font' => [
					'desktop' => [
						'value' => [
							'family' => 'Cormorant Garamond',
							'size'   => '1.5rem',
							'color'  => '#1E3D2C',
						],
					],
				],
			],
		],
	],
	'meta'   => [
		'advanced'   => [
			'showAuthor'     => ['desktop' => ['value' => 'off']],
			'showDate'       => ['desktop' => ['value' => 'on']],
			'showCategories' => ['desktop' => ['value' => 'off']],
			'showComments'   => ['desktop' => ['value' => 'off']],
		],
		'decoration' => [
			'font' => [
				'font' => [
					'desktop' => [
						'value' => [
							'family' => 'Source Sans 3',
							'size'   => '0.9rem',
							'color'  => '#4A534C',
						],
					],
				],
			],
		],
	],
	'content' => [
		'advanced' => [
			'showContent' => ['desktop' => ['value' => 'off']],
			'showMore'    => ['desktop' => ['value' => 'off']],
		],
	],
	'pagination' => [
		'advanced' => [
			'showPagination' => ['desktop' => ['value' => 'on']],
		],
	],
];

$json_flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
$banner_json = wp_json_encode($banner_text, $json_flags);
$blog_json = wp_json_encode($blog, $json_flags);

$content = <<<BLK
<!-- wp:divi/section {"builderVersion":"5.0.0-public-alpha.18.2","modulePreset":["default"],"module":{"decoration":{"spacing":{"desktop":{"value":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px","syncVertical":"off","syncHorizontal":"off"}}}},"background":{"desktop":{"value":{"color":"#1E3D2C"}}},"layout":{"desktop":{"value":{"display":"block"}}}},"advanced":{"htmlAttributes":{"desktop":{"value":{"class":"as-news-banner-section"}}}}}} -->
<!-- wp:divi/row {"builderVersion":"5.0.0-public-alpha.18.2","modulePreset":["default"],"module":{"decoration":{"spacing":{"desktop":{"value":{"padding":{"top":"4rem","right":"1.25rem","bottom":"2.5rem","left":"1.25rem","syncVertical":"off","syncHorizontal":"off"}}}},"sizing":{"desktop":{"value":{"width":"100%","maxWidth":"80rem"}}},"layout":{"desktop":{"value":{"display":"block"}}}}}} -->
<!-- wp:divi/column {"module":{"advanced":{"type":{"desktop":{"value":"4_4"}}},"decoration":{"layout":{"desktop":{"value":{"display":"block"}}}}},"builderVersion":"5.0.0-public-alpha.18.2","modulePreset":["default"]} -->
<!-- wp:divi/text {$banner_json} -->
<!-- /wp:divi/text -->
<!-- /wp:divi/column -->
<!-- /wp:divi/row -->
<!-- /wp:divi/section -->
<!-- wp:divi/section {"builderVersion":"5.0.0-public-alpha.18.2","modulePreset":["default"],"module":{"decoration":{"spacing":{"desktop":{"value":{"padding":{"top":"0px","right":"0px","bottom":"4.5rem","left":"0px","syncVertical":"off","syncHorizontal":"off"}}}},"background":{"desktop":{"value":{"color":"rgba(255,255,255,0.45)"}}},"layout":{"desktop":{"value":{"display":"block"}}}},"advanced":{"htmlAttributes":{"desktop":{"value":{"class":"as-news-list-section"}}}}}} -->
<!-- wp:divi/row {"builderVersion":"5.0.0-public-alpha.18.2","modulePreset":["default"],"module":{"decoration":{"spacing":{"desktop":{"value":{"padding":{"top":"2rem","right":"1.25rem","bottom":"0px","left":"1.25rem","syncVertical":"off","syncHorizontal":"off"}}}},"sizing":{"desktop":{"value":{"width":"100%","maxWidth":"56rem"}}},"layout":{"desktop":{"value":{"display":"block"}}}}}} -->
<!-- wp:divi/column {"module":{"advanced":{"type":{"desktop":{"value":"4_4"}}},"decoration":{"layout":{"desktop":{"value":{"display":"block"}}}}},"builderVersion":"5.0.0-public-alpha.18.2","modulePreset":["default"]} -->
<!-- wp:divi/blog {$blog_json} /-->
<!-- /wp:divi/column -->
<!-- /wp:divi/row -->
<!-- /wp:divi/section -->
BLK;

wp_update_post([
	'ID'           => $news->ID,
	'post_title'   => 'News & updates',
	'post_content' => wp_slash($content),
	'post_status'  => 'publish',
]);
update_post_meta($news->ID, '_et_pb_use_builder', 'on');
update_post_meta($news->ID, '_et_pb_page_layout', 'et_no_sidebar');
update_post_meta($news->ID, '_et_pb_show_title', 'off');
update_post_meta($news->ID, '_et_builder_version', '5.0.0');

$saved = get_post($news->ID)->post_content;
$ok_banner = (strpos($saved, 'as-eyebrow') !== false && strpos($saved, 'Newsroom') !== false);
$ok_thumbs = (bool) preg_match('/"enable"\s*:\s*\{\s*"desktop"\s*:\s*\{\s*"value"\s*:\s*"on"/', $saved);

echo "News page #{$news->ID} rebuilt\n";
echo $ok_banner ? "Banner HTML OK\n" : "Banner HTML MISSING\n";
echo $ok_thumbs ? "Thumbnails ON\n" : "Thumbnails NOT on\n";

// Validate banner text JSON still parses.
if (preg_match('/<!-- wp:divi\/text (\{.*?\}) -->/s', $saved, $m)) {
	$decoded = json_decode($m[1], true);
	echo $decoded ? "Banner JSON OK\n" : ("Banner JSON FAIL: " . json_last_error_msg() . "\n");
}

if (function_exists('wp_cache_flush')) {
	wp_cache_flush();
}

echo "Done. Hard-refresh /news/\n";
