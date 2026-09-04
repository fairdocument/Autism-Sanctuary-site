<?php
/**
 * Create/update the "Featured in UVA Today!" news post.
 * Run: wp eval-file wordpress-migration/create-uva-today-post.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$path = __DIR__ . '/news-export/post-featured-in-uva-today.json';
$data = json_decode((string) file_get_contents($path), true);
if (!$data) {
	echo "Invalid JSON export\n";
	return;
}

$slug = $data['slug'];
$existing = get_posts([
	'name'           => $slug,
	'post_type'      => 'post',
	'post_status'    => 'any',
	'posts_per_page' => 1,
	'fields'         => 'ids',
]);

$args = [
	'post_title'   => $data['title'],
	'post_name'    => $slug,
	'post_content' => $data['content'],
	'post_excerpt' => $data['excerpt'],
	'post_status'  => 'publish',
	'post_type'    => 'post',
	'post_date'    => $data['date'],
	'post_date_gmt' => get_gmt_from_date($data['date']),
];

if ($existing) {
	$args['ID'] = (int) $existing[0];
	$id = wp_update_post(wp_slash($args), true);
	echo "Updated post #{$id}\n";
} else {
	$id = wp_insert_post(wp_slash($args), true);
	echo "Created post #{$id}\n";
}

if (is_wp_error($id)) {
	echo $id->get_error_message() . "\n";
	return;
}

delete_post_meta($id, '_et_pb_use_builder');
delete_post_meta($id, '_et_builder_version');
update_post_meta($id, '_et_pb_page_layout', 'et_no_sidebar');
update_post_meta($id, '_et_pb_side_nav', 'off');
update_post_meta($id, '_et_pb_show_title', 'on');

$thumb_url = trim((string) ($data['thumb_url'] ?? ''));
$thumb_id = 0;
if ($thumb_url !== '') {
	$basename = basename(parse_url($thumb_url, PHP_URL_PATH) ?: $thumb_url);
	$found = get_posts([
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
	if ($found) {
		$thumb_id = (int) $found[0];
	} else {
		$tmp = download_url($thumb_url);
		if (is_wp_error($tmp)) {
			echo 'Thumb download failed: ' . $tmp->get_error_message() . "\n";
		} else {
			$file_array = [
				'name'     => $basename,
				'tmp_name' => $tmp,
			];
			$thumb_id = media_handle_sideload($file_array, $id, $data['title']);
			if (is_wp_error($thumb_id)) {
				@unlink($tmp);
				echo 'Thumb sideload failed: ' . $thumb_id->get_error_message() . "\n";
				$thumb_id = 0;
			}
		}
	}
}

if ($thumb_id) {
	set_post_thumbnail($id, $thumb_id);
	update_post_meta($thumb_id, '_wp_attachment_image_alt', 'Ty Hopkins and Megan McGrath petting a cow at Autism Sanctuary');
	echo "Featured image #{$thumb_id}\n";
} else {
	echo "No featured image assigned\n";
}

echo 'URL: ' . get_permalink($id) . "\n";
echo "Done.\n";
