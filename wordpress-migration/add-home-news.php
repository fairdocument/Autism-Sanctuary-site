<?php
/**
 * Add Latest updates (3 posts + All news) to the homepage after Looking ahead.
 * Run: wp eval-file wordpress-migration/add-home-news.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

$home_id = (int) get_option('page_on_front');
if (!$home_id) {
	$home = get_page_by_path('home');
	$home_id = $home ? (int) $home->ID : 0;
}
if (!$home_id) {
	echo "Home page not found\n";
	return;
}

$json_flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

$intro_html = '<p class="as-eyebrow">Newsroom</p>
<h2>Latest updates</h2>
<p class="as-lede">Farm stories, press, and program notes from Autism Sanctuary.</p>';

$intro_attrs = wp_json_encode([
	'builderVersion' => '5.11.1',
	'modulePreset'   => ['default'],
	'module'         => [
		'advanced' => [
			'htmlAttributes' => [
				'desktop' => ['value' => ['class' => 'as-home-news-intro']],
			],
		],
		'decoration' => [
			'spacing' => [
				'desktop' => [
					'value' => [
						'margin' => [
							'top'            => '0px',
							'right'          => '',
							'bottom'         => '1.5rem',
							'left'           => '',
							'syncVertical'   => 'off',
							'syncHorizontal' => 'off',
						],
					],
				],
			],
		],
	],
	'content'        => [
		'innerContent' => [
			'desktop' => ['value' => $intro_html],
		],
	],
	'decoration'     => [
		'bodyFont'    => [
			'body' => [
				'font' => [
					'desktop' => [
						'value' => [
							'family' => 'Source Sans 3',
							'weight' => '400',
							'color'  => '#4A534C',
						],
					],
				],
			],
		],
		'headingFont' => [
			'h1' => [
				'font' => [
					'desktop' => [
						'value' => [
							'family' => 'Cormorant Garamond',
							'weight' => '400',
							'color'  => '#1E3D2C',
						],
					],
				],
			],
		],
	],
], $json_flags);

$blog_attrs = wp_json_encode([
	'builderVersion' => '5.11.1',
	'modulePreset'   => ['default'],
	'module'         => [
		'advanced'   => [
			'htmlAttributes' => [
				'desktop' => ['value' => ['class' => 'as-news-blog as-home-news-blog']],
			],
		],
		'decoration' => [
			'spacing' => [
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
		],
	],
	'post'           => [
		'advanced'   => [
			'number'         => ['desktop' => ['value' => '3']],
			'dateFormat'     => ['desktop' => ['value' => 'F j, Y']],
			'excerptContent' => ['desktop' => ['value' => 'off']],
			'showExcerpt'    => ['desktop' => ['value' => 'on']],
			'excerptLength'  => ['desktop' => ['value' => '22']],
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
	'image'          => [
		'advanced' => [
			'enable' => ['desktop' => ['value' => 'on']],
		],
	],
	'header'         => [
		'advanced'   => [
			'level' => ['desktop' => ['value' => 'h3']],
		],
		'decoration' => [
			'font' => [
				'font' => [
					'desktop' => [
						'value' => [
							'family' => 'Cormorant Garamond',
							'size'   => '1.35rem',
							'color'  => '#1E3D2C',
						],
					],
				],
			],
		],
	],
	'meta'           => [
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
	'content'        => [
		'advanced' => [
			'showContent' => ['desktop' => ['value' => 'off']],
			'showMore'    => ['desktop' => ['value' => 'off']],
		],
	],
	'pagination'     => [
		'advanced' => [
			'showPagination' => ['desktop' => ['value' => 'off']],
		],
	],
], $json_flags);

$more_html = '<p><a class="as-btn as-btn--ghost" href="/news/">All news</a></p>';
$more_attrs = wp_json_encode([
	'builderVersion' => '5.11.1',
	'modulePreset'   => ['default'],
	'module'         => [
		'advanced'   => [
			'htmlAttributes' => [
				'desktop' => ['value' => ['class' => 'as-home-news-more']],
			],
		],
		'decoration' => [
			'spacing' => [
				'desktop' => [
					'value' => [
						'margin' => [
							'top'            => '2rem',
							'right'          => '',
							'bottom'         => '0px',
							'left'           => '',
							'syncVertical'   => 'off',
							'syncHorizontal' => 'off',
						],
					],
				],
			],
		],
	],
	'content'        => [
		'innerContent' => [
			'desktop' => ['value' => $more_html],
		],
	],
], $json_flags);

$news_section = <<<BLK
<!-- wp:divi/section {"builderVersion":"5.11.1","modulePreset":["default"],"module":{"decoration":{"spacing":{"desktop":{"value":{"padding":{"top":"4rem","right":"0px","bottom":"4.5rem","left":"0px","syncVertical":"off","syncHorizontal":"off"}}}},"background":{"desktop":{"value":{"color":"rgba(255,255,255,0.45)"}}},"layout":{"desktop":{"value":{"display":"block"}}}},"advanced":{"htmlAttributes":{"desktop":{"value":{"class":"as-home-news"}}}}}} -->
<!-- wp:divi/row {"builderVersion":"5.11.1","modulePreset":["default"],"module":{"decoration":{"sizing":{"desktop":{"value":{"width":"100%","maxWidth":"56rem"}}},"spacing":{"desktop":{"value":{"padding":{"top":"0px","right":"1.25rem","bottom":"0px","left":"1.25rem","syncVertical":"off","syncHorizontal":"off"}}}},"layout":{"desktop":{"value":{"display":"block"}}}}}} -->
<!-- wp:divi/column {"module":{"advanced":{"type":{"desktop":{"value":"4_4"}}},"decoration":{"layout":{"desktop":{"value":{"display":"block"}}}}},"builderVersion":"5.11.1","modulePreset":["default"]} -->
<!-- wp:divi/text {$intro_attrs} -->
<!-- /wp:divi/text -->
<!-- wp:divi/blog {$blog_attrs} /-->
<!-- wp:divi/code {$more_attrs} /-->
<!-- /wp:divi/column -->
<!-- /wp:divi/row -->
<!-- /wp:divi/section -->
BLK;

$content = get_post_field('post_content', $home_id);
if (!is_string($content) || $content === '') {
	echo "Home content empty\n";
	return;
}

// Remove a previously inserted home-news section (idempotent).
$content = preg_replace(
	'/<!-- wp:divi\/section \{[^\n]*as-home-news[^\n]*\} -->.*?<!-- \/wp:divi\/section -->\s*/s',
	'',
	$content
);

$marker = 'Want to Learn More';
$want_pos = strpos($content, $marker);
if ($want_pos === false) {
	echo "Could not find Want to Learn More section\n";
	return;
}

$section_open = strrpos(substr($content, 0, $want_pos), '<!-- wp:divi/section');
if ($section_open === false) {
	echo "Could not find section open before Want to Learn More\n";
	return;
}

$new_content = substr($content, 0, $section_open) . $news_section . "\n\n" . substr($content, $section_open);

$updated = wp_update_post([
	'ID'           => $home_id,
	'post_content' => wp_slash($new_content),
], true);

if (is_wp_error($updated)) {
	echo $updated->get_error_message() . "\n";
	return;
}

update_post_meta($home_id, '_et_pb_use_builder', 'on');
update_post_meta($home_id, '_et_builder_version', '5.11.1');

$saved = get_post_field('post_content', $home_id);
echo "Home #{$home_id} updated\n";
echo (strpos($saved, 'as-home-news') !== false) ? "Home news section OK\n" : "Home news section MISSING\n";
echo (strpos($saved, 'as-home-news-blog') !== false) ? "Blog class OK\n" : "Blog class MISSING\n";
echo (strpos($saved, 'All news') !== false) ? "All news link OK\n" : "All news link MISSING\n";

foreach (['divi/text', 'divi/blog', 'divi/code'] as $mod) {
	if (preg_match('/<!-- wp:' . preg_quote($mod, '/') . ' (\{.*?\})(?: -->| \/-->)/s', $news_section, $m)) {
		$ok = json_decode($m[1], true);
		echo $mod . ': ' . ($ok ? "JSON OK\n" : ('JSON FAIL ' . json_last_error_msg() . "\n"));
	}
}

if (class_exists('ET_Core_PageResource')) {
	ET_Core_PageResource::remove_static_resources('all', 'all');
	echo "Cleared Divi static CSS\n";
}
if (function_exists('wp_cache_flush')) {
	wp_cache_flush();
}

echo "Done. Hard-refresh the homepage.\n";
