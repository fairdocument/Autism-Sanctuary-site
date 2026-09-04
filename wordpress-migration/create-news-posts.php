<?php
/**
 * Create/update news posts from selected news-export JSON files.
 * Run: wp eval-file wordpress-migration/create-news-posts.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$files = [
	'post-give-where-you-live.json',
	'post-wahs-western-hemisphere-feature.json',
	'post-mpo-paratransit-vehicle-funding.json',
	'post-tour-of-te-meats.json',
];

$dir = __DIR__ . '/news-export';

foreach ($files as $file) {
	$path = $dir . '/' . $file;
	$data = json_decode((string) file_get_contents($path), true);
	if (!$data || empty($data['slug'])) {
		echo "Skip invalid: {$file}\n";
		continue;
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
		'post_title'    => $data['title'],
		'post_name'     => $slug,
		'post_content'  => $data['content'],
		'post_excerpt'  => $data['excerpt'] ?? '',
		'post_status'   => 'publish',
		'post_type'     => 'post',
		'post_date'     => $data['date'],
		'post_date_gmt' => get_gmt_from_date($data['date']),
	];

	if ($existing) {
		$args['ID'] = (int) $existing[0];
		$id = wp_update_post(wp_slash($args), true);
		$label = 'Updated';
	} else {
		$id = wp_insert_post(wp_slash($args), true);
		$label = 'Created';
	}

	if (is_wp_error($id)) {
		echo "{$slug}: " . $id->get_error_message() . "\n";
		continue;
	}

	delete_post_meta($id, '_et_pb_use_builder');
	delete_post_meta($id, '_et_builder_version');
	update_post_meta($id, '_et_pb_page_layout', 'et_no_sidebar');
	update_post_meta($id, '_et_pb_side_nav', 'off');
	update_post_meta($id, '_et_pb_show_title', 'on');

	$thumb_id = 0;
	$thumb_url = trim((string) ($data['thumb_url'] ?? ''));
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
				echo "  thumb failed ({$slug}): " . $tmp->get_error_message() . "\n";
			} else {
				$thumb_id = media_handle_sideload([
					'name'     => $basename,
					'tmp_name' => $tmp,
				], $id, $data['title']);
				if (is_wp_error($thumb_id)) {
					@unlink($tmp);
					echo "  sideload failed ({$slug}): " . $thumb_id->get_error_message() . "\n";
					$thumb_id = 0;
				}
			}
		}
	}

	if ($thumb_id) {
		set_post_thumbnail($id, $thumb_id);
		if (!empty($data['thumb_alt'])) {
			update_post_meta($thumb_id, '_wp_attachment_image_alt', $data['thumb_alt']);
		}
	}

	echo "{$label} {$slug} (#{$id}) thumb=#{$thumb_id} " . get_permalink($id) . "\n";
}

echo "Done.\n";
