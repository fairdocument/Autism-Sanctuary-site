<?php
/**
 * Rebuild News as a Divi-editable page + Theme Builder single-post template.
 * Run: wp eval-file wordpress-migration/setup-news.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

echo "=== News / Divi setup ===\n";

$news = get_page_by_path('news');
if (!$news) {
	echo "News page missing\n";
	return;
}

// News must NOT be the Posts page — otherwise Divi Builder content is ignored.
update_option('page_for_posts', 0);
echo "Cleared page_for_posts so News is Divi-editable\n";

$news_content = <<<'HTML'
[et_pb_section fb_built="1" _builder_version="4.27.4" _module_preset="default" custom_padding="4rem|0px|2.5rem|0px|false|false" background_color="RGBA(255,255,255,0)"][et_pb_row _builder_version="4.27.4" _module_preset="default" custom_padding="0px|0px|0px|0px|false|false" max_width="72rem"][et_pb_column type="4_4" _builder_version="4.27.4" _module_preset="default"][et_pb_text _builder_version="4.27.4" _module_preset="default" module_class="as-news-banner"]
<p class="as-eyebrow">Newsroom</p>
<h1>News &amp; updates</h1>
<p class="as-lede">Farm stories, program updates, and community notes from Autism Sanctuary.</p>
[/et_pb_text][/et_pb_column][/et_pb_row][/et_pb_section][et_pb_section fb_built="1" _builder_version="4.27.4" _module_preset="default" custom_padding="0px|0px|4.5rem|0px|false|false" background_color="rgba(255,255,255,0.45)" module_class="as-news-list-section"][et_pb_row _builder_version="4.27.4" _module_preset="default" max_width="44rem" custom_padding="2rem|0px|0px|0px|false|false"][et_pb_column type="4_4" _builder_version="4.27.4" _module_preset="default"][et_pb_blog fullwidth="on" posts_number="10" meta_date="F j, Y" show_thumbnail="off" show_content="off" show_more="off" show_author="off" show_date="on" show_categories="off" show_comments="off" show_excerpt="on" excerpt_length="32" show_pagination="on" use_overlay="off" _builder_version="4.27.4" _module_preset="default" module_class="as-news-blog" header_level="h2" header_font="Cormorant Garamond||||||||" header_font_size="1.5rem" header_text_color="#1E3D2C" body_font="Source Sans 3||||||||" body_text_color="#4A534C" meta_font="Source Sans 3||||||||" meta_text_color="#4A534C" meta_font_size="0.9rem" border_width_all="0px" custom_padding="0px|0px|0px|0px|false|false" /][/et_pb_column][/et_pb_row][/et_pb_section]
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
echo "News page #{$news->ID} rebuilt with Divi Blog module\n";

/**
 * Build a plain-text excerpt from Divi 4/5 post content.
 */
function as_news_make_excerpt($content) {
	$raw = (string) $content;
	$raw = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', static function ($m) {
		return mb_convert_encoding(pack('H*', $m[1]), 'UTF-8', 'UCS-2BE');
	}, $raw);
	$raw = str_replace(['\\n', '\\/', '\\"'], ["\n", '/', '"'], $raw);
	$raw = wp_strip_all_tags($raw);
	$raw = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	$raw = preg_replace('/\s+/', ' ', $raw);
	$raw = trim($raw);
	if (strlen($raw) < 40) {
		return '';
	}
	// Prefer starting at a real sentence if builder junk precedes it.
	if (preg_match('/(Autism Sanctuary|[A-Z][^\\.]{20,})/u', $raw, $m, PREG_OFFSET_CAPTURE)) {
		$raw = substr($raw, $m[0][1]);
	}
	return wp_html_excerpt($raw, 180, '…');
}

// Ensure every post has an excerpt + full-width layout for fallback.
$posts = get_posts([
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'posts_per_page' => 50,
]);
foreach ($posts as $post) {
	update_post_meta($post->ID, '_et_pb_page_layout', 'et_no_sidebar');
	update_post_meta($post->ID, '_et_pb_side_nav', 'off');
	update_post_meta($post->ID, '_et_pb_show_title', 'on');

	$excerpt = as_news_make_excerpt($post->post_content);
	if ($excerpt && $excerpt !== trim((string) $post->post_excerpt)) {
		wp_update_post([
			'ID'           => $post->ID,
			'post_excerpt' => $excerpt,
		]);
		echo "Excerpt set for #{$post->ID}\n";
	}
}

// Theme Builder needs an authenticated admin (CLI user is 0).
$admins = get_users(['role' => 'administrator', 'number' => 1]);
if ($admins) {
	wp_set_current_user((int) $admins[0]->ID);
}

// Theme Builder: single post body (editable in Divi → Theme Builder)
if (!function_exists('et_theme_builder_insert_layout') || !function_exists('et_theme_builder_store_template')) {
	echo "Theme Builder APIs unavailable — skip single-post template\n";
} elseif (!current_user_can('edit_others_posts')) {
	echo "No admin capability for Theme Builder APIs — skip single-post template\n";
} else {
	$tb_id = et_theme_builder_get_theme_builder_post_id(true, true);

	$body_shortcode = <<<'HTML'
[et_pb_section fb_built="1" _builder_version="4.27.4" _module_preset="default" custom_padding="3.5rem|0px|4.5rem|0px|false|false" background_color="RGBA(255,255,255,0)" module_class="as-single-post"][et_pb_row _builder_version="4.27.4" _module_preset="default" max_width="44rem" custom_padding="0px|1.25rem|0px|1.25rem|false|false"][et_pb_column type="4_4" _builder_version="4.27.4" _module_preset="default"][et_pb_post_title title="on" meta="on" author="off" date="on" categories="off" comments="off" featured_image="off" title_level="h1" _builder_version="4.27.4" _module_preset="default" module_class="as-single-title" title_font="Cormorant Garamond||||||||" title_font_size="clamp(2rem, 4vw, 3rem)" title_text_color="#1E3D2C" meta_font="Source Sans 3||||||||" meta_text_color="#4A534C" meta_font_size="0.9rem" custom_margin="||1.5rem||false|false" /][et_pb_post_content _builder_version="4.27.4" _module_preset="default" module_class="as-single-content as-prose" text_font="Source Sans 3||||||||" text_text_color="#4A534C" header_font="Cormorant Garamond||||||||" header_text_color="#1E3D2C" /][et_pb_text _builder_version="4.27.4" _module_preset="default" module_class="as-single-back" custom_margin="2.5rem||||false|false"]
<p><a class="as-btn as-btn--ghost" href="/news/">← All news</a></p>
[/et_pb_text][/et_pb_column][/et_pb_row][/et_pb_section]
HTML;

	// Reuse existing AS single-post body if present.
	$existing_body = get_posts([
		'post_type'      => ET_THEME_BUILDER_BODY_LAYOUT_POST_TYPE,
		'post_status'    => 'publish',
		'title'          => 'AS Single Post Body',
		'posts_per_page' => 1,
		'fields'         => 'ids',
	]);
	// WP_Query title param is unreliable; search by meta.
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
			// Attach to Theme Builder post if not already listed.
			$ids = et_theme_builder_get_theme_builder_template_ids(true, $tb_id);
			if (!in_array((int) $stored, $ids, true)) {
				add_post_meta($tb_id, '_et_template', (int) $stored);
			}
			echo "Theme Builder template #{$stored} assigned to All Posts\n";
		}
	}
}

echo "=== Done ===\n";
echo "Edit News layout: Pages → News → Edit with Divi\n";
echo "Edit single-post chrome: Divi → Theme Builder → AS All Posts\n";
echo "Add/edit stories: Posts → Add New (content fills the Theme Builder body)\n";
