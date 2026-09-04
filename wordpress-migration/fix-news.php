<?php
/**
 * Fix News index + single posts:
 * - Restore clean article HTML from news-export/
 * - Sideload / assign featured images (thumbnails)
 * - Rebuild /news/ Divi Blog module with thumbnails on
 * - Repair Theme Builder single-post body (back link + featured image)
 *
 * Run: wp eval-file wordpress-migration/fix-news.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

echo "=== Fix news ===\n";

$export_dir = __DIR__ . '/news-export';
$files = glob($export_dir . '/post-*.json');
if (!$files) {
	echo "No news-export JSON files found\n";
	return;
}

/**
 * Sideload a remote image once and return attachment ID.
 */
function as_news_sideload_image($url, $parent_id = 0, $title = '') {
	$url = trim((string) $url);
	if ($url === '') {
		return 0;
	}

	// Reuse existing attachment with same basename when possible.
	$basename = basename(parse_url($url, PHP_URL_PATH) ?: $url);
	$basename = preg_replace('/\.jpg\.jpg$/i', '.jpg', $basename);
	$existing = get_posts([
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_query'     => [
			[
				'key'     => '_wp_attached_file',
				'value'   => $basename,
				'compare' => 'LIKE',
			],
		],
	]);
	if ($existing) {
		return (int) $existing[0];
	}

	$tmp = download_url($url);
	if (is_wp_error($tmp)) {
		echo "  thumb download failed: " . $tmp->get_error_message() . "\n";
		return 0;
	}

	$file_array = [
		'name'     => $basename,
		'tmp_name' => $tmp,
	];
	$id = media_handle_sideload($file_array, $parent_id, $title ?: null);
	if (is_wp_error($id)) {
		@unlink($tmp);
		echo "  thumb sideload failed: " . $id->get_error_message() . "\n";
		return 0;
	}
	return (int) $id;
}

/**
 * Fallback farm photo when a post has no historic featured image.
 */
function as_news_fallback_thumb_id() {
	static $id = null;
	if ($id !== null) {
		return $id;
	}
	$candidates = ['IMG_5777', 'IMG_2421', 'Banner', 'Pavilion edited', 'IMG_2330'];
	foreach ($candidates as $title) {
		$q = new WP_Query([
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'title'          => $title,
		]);
		if ($q->posts) {
			$id = (int) $q->posts[0];
			return $id;
		}
	}
	$q = new WP_Query([
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'post_mime_type' => 'image',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'orderby'        => 'date',
		'order'          => 'DESC',
	]);
	$id = $q->posts ? (int) $q->posts[0] : 0;
	return $id;
}

foreach ($files as $file) {
	$data = json_decode((string) file_get_contents($file), true);
	if (!$data || empty($data['slug']) || empty($data['content'])) {
		echo "Skip bad export: {$file}\n";
		continue;
	}

	$slug = $data['slug'];
	$q = new WP_Query([
		'name'           => $slug,
		'post_type'      => 'post',
		'post_status'    => 'any',
		'posts_per_page' => 1,
	]);
	if (!$q->have_posts()) {
		echo "Missing post for {$slug}\n";
		continue;
	}
	$post = $q->posts[0];
	$id = (int) $post->ID;

	$content = (string) $data['content'];
	// Prefer local/staging media paths for any leftover absolute hosts.
	$content = preg_replace('#https?://(www\.)?autismsanctuary\.org/#', '/', $content);
	$content = preg_replace('#https?://archive\.autismsanctuary\.org/#', 'https://archive.autismsanctuary.org/', $content);

	$excerpt = trim((string) ($data['excerpt'] ?? ''));
	if ($excerpt === '') {
		$excerpt = wp_html_excerpt(wp_strip_all_tags($content), 180, '…');
	}

	wp_update_post([
		'ID'           => $id,
		'post_title'   => $data['title'] ?? $post->post_title,
		'post_content' => wp_slash($content),
		'post_excerpt' => wp_slash($excerpt),
		'post_status'  => 'publish',
	]);

	// Plain HTML inside Theme Builder Post Content — not nested Divi sections.
	delete_post_meta($id, '_et_pb_use_builder');
	delete_post_meta($id, '_et_builder_version');
	update_post_meta($id, '_et_pb_page_layout', 'et_no_sidebar');
	update_post_meta($id, '_et_pb_side_nav', 'off');
	update_post_meta($id, '_et_pb_show_title', 'on');

	$thumb_id = 0;
	if (!empty($data['thumb_url'])) {
		$thumb_id = as_news_sideload_image($data['thumb_url'], $id, $data['title'] ?? $slug);
	}
	if (!$thumb_id) {
		$thumb_id = as_news_fallback_thumb_id();
	}
	if ($thumb_id) {
		set_post_thumbnail($id, $thumb_id);
		echo "Post OK: {$slug} (#{$id}) thumb=#{$thumb_id}\n";
	} else {
		echo "Post OK: {$slug} (#{$id}) (no thumb)\n";
	}
}

// --- Rebuild News page with thumbnails ---
$news = get_page_by_path('news');
if (!$news) {
	echo "News page missing\n";
	return;
}

update_option('page_for_posts', 0);

$news_content = <<<'HTML'
[et_pb_section fb_built="1" _builder_version="4.27.4" _module_preset="default" custom_padding="4rem|0px|2.5rem|0px|false|false" background_color="RGBA(255,255,255,0)" module_class="as-news-banner-section"][et_pb_row _builder_version="4.27.4" _module_preset="default" custom_padding="0px|0px|0px|0px|false|false" max_width="72rem"][et_pb_column type="4_4" _builder_version="4.27.4" _module_preset="default"][et_pb_text _builder_version="4.27.4" _module_preset="default" module_class="as-news-banner"]
<p class="as-eyebrow">Newsroom</p>
<h1>News &amp; updates</h1>
<p class="as-lede">Farm stories, program updates, and community notes from Autism Sanctuary.</p>
[/et_pb_text][/et_pb_column][/et_pb_row][/et_pb_section][et_pb_section fb_built="1" _builder_version="4.27.4" _module_preset="default" custom_padding="0px|0px|4.5rem|0px|false|false" background_color="rgba(255,255,255,0.45)" module_class="as-news-list-section"][et_pb_row _builder_version="4.27.4" _module_preset="default" max_width="56rem" custom_padding="2rem|1.25rem|0px|1.25rem|false|false"][et_pb_column type="4_4" _builder_version="4.27.4" _module_preset="default"][et_pb_blog fullwidth="on" posts_number="10" meta_date="F j, Y" show_thumbnail="on" show_content="off" show_more="off" show_author="off" show_date="on" show_categories="off" show_comments="off" show_excerpt="on" excerpt_length="28" show_pagination="on" use_overlay="off" _builder_version="4.27.4" _module_preset="default" module_class="as-news-blog" header_level="h2" header_font="Cormorant Garamond||||||||" header_font_size="1.5rem" header_text_color="#1E3D2C" body_font="Source Sans 3||||||||" body_text_color="#4A534C" meta_font="Source Sans 3||||||||" meta_text_color="#4A534C" meta_font_size="0.9rem" border_width_all="0px" custom_padding="0px|0px|0px|0px|false|false" /][/et_pb_column][/et_pb_row][/et_pb_section]
HTML;

wp_update_post([
	'ID'           => $news->ID,
	'post_title'   => 'News & updates',
	'post_content' => $news_content,
	'post_status'  => 'publish',
]);
update_post_meta($news->ID, '_et_pb_use_builder', 'on');
update_post_meta($news->ID, '_et_pb_page_layout', 'et_no_sidebar');
update_post_meta($news->ID, '_et_pb_show_title', 'off');
update_post_meta($news->ID, '_et_builder_version', '4.27.4');
echo "News page #{$news->ID} rebuilt with thumbnails\n";

// --- Theme Builder single post ---
$admins = get_users(['role' => 'administrator', 'number' => 1]);
if ($admins) {
	wp_set_current_user((int) $admins[0]->ID);
}

if (!function_exists('et_theme_builder_insert_layout') || !function_exists('et_theme_builder_store_template')) {
	echo "Theme Builder APIs unavailable — skip single-post template\n";
} elseif (!current_user_can('edit_others_posts')) {
	echo "No admin capability for Theme Builder APIs — skip single-post template\n";
} else {
	$tb_id = et_theme_builder_get_theme_builder_post_id(true, true);

	// Use Code module for back link so Divi 5 conversion cannot corrupt HTML entities.
	$body_shortcode = <<<'HTML'
[et_pb_section fb_built="1" _builder_version="4.27.4" _module_preset="default" custom_padding="3.5rem|0px|4.5rem|0px|false|false" background_color="RGBA(255,255,255,0)" module_class="as-single-post"][et_pb_row _builder_version="4.27.4" _module_preset="default" max_width="44rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false"][et_pb_column type="4_4" _builder_version="4.27.4" _module_preset="default"][et_pb_post_title title="on" meta="on" author="off" date="on" categories="off" comments="off" featured_image="on" title_level="h1" _builder_version="4.27.4" _module_preset="default" module_class="as-single-title" title_font="Cormorant Garamond||||||||" title_font_size="clamp(2rem, 4vw, 3rem)" title_text_color="#1E3D2C" meta_font="Source Sans 3||||||||" meta_text_color="#4A534C" meta_font_size="0.9rem" custom_margin="||1.5rem||false|false" /][et_pb_post_content _builder_version="4.27.4" _module_preset="default" module_class="as-single-content as-prose" text_font="Source Sans 3||||||||" text_text_color="#4A534C" header_font="Cormorant Garamond||||||||" header_text_color="#1E3D2C" /][et_pb_code _builder_version="4.27.4" _module_preset="default" module_class="as-single-back" custom_margin="2.5rem||||false|false"]
<p><a class="as-btn as-btn--ghost" href="/news/">&larr; All news</a></p>
[/et_pb_code][/et_pb_column][/et_pb_row][/et_pb_section]
HTML;

	$body_id = (int) get_option('as_tb_single_post_body_id');
	if ($body_id && get_post_type($body_id) === ET_THEME_BUILDER_BODY_LAYOUT_POST_TYPE) {
		wp_update_post([
			'ID'           => $body_id,
			'post_content' => $body_shortcode,
			'post_title'   => 'AS Single Post Body',
		]);
		echo "Updated single-post body layout #{$body_id}\n";
	} else {
		$body_id = et_theme_builder_insert_layout([
			'post_type'    => ET_THEME_BUILDER_BODY_LAYOUT_POST_TYPE,
			'post_title'   => 'AS Single Post Body',
			'post_content' => $body_shortcode,
		]);
		if (is_wp_error($body_id)) {
			echo 'Body layout error: ' . $body_id->get_error_message() . "\n";
			$body_id = 0;
		} else {
			update_option('as_tb_single_post_body_id', (int) $body_id);
			echo "Created single-post body layout #{$body_id}\n";
		}
	}

	if ($body_id) {
		update_post_meta($body_id, '_et_pb_use_builder', 'on');
		update_post_meta($body_id, '_et_builder_version', '4.27.4');

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
		if (!$stored) {
			echo "Failed to store Theme Builder template\n";
		} else {
			update_option('as_tb_single_post_template_id', (int) $stored);
			$ids = et_theme_builder_get_theme_builder_template_ids(true, $tb_id);
			if (!in_array((int) $stored, $ids, true)) {
				add_post_meta($tb_id, '_et_template', (int) $stored);
			}
			echo "Theme Builder template #{$stored} assigned to All Posts\n";
		}
	}
}

// Convert shortcodes → Divi 5 blocks.
if (class_exists('\\ET\\Builder\\Packages\\Conversion\\Conversion')) {
	if ($admins) {
		wp_set_current_user((int) $admins[0]->ID);
	}
	\ET\Builder\Packages\Conversion\Conversion::initialize_shortcode_framework();
	foreach (array_filter([$news->ID, (int) get_option('as_tb_single_post_body_id')]) as $convert_id) {
		$post = get_post($convert_id);
		if (!$post || strpos($post->post_content, '[et_pb') === false) {
			continue;
		}
		$converted = \ET\Builder\Packages\Conversion\Conversion::maybeConvertContent(
			$post->post_content,
			true,
			(int) $convert_id,
			true
		);
		if ($converted && $converted !== $post->post_content) {
			wp_update_post([
				'ID'           => $convert_id,
				'post_content' => wp_slash($converted),
			]);
			echo "Converted #{$convert_id} to Divi 5 blocks\n";
		}
	}

}

// Rebuild back-link Code module with JSON-safe attributes (shortcode conversion corrupts HTML).
require __DIR__ . '/fix-news-tb-back.php';

echo "=== Done ===\n";
echo "Verify: /news/ and a few /{post-slug}/ URLs\n";
