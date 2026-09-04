<?php
/**
 * Update MPO funding post + create bus/minivan arrival posts.
 * Run: wp eval-file wordpress-migration/create-vehicle-news.php
 *
 * Expects /tmp/CA-MPO-logo-hero.jpg and /tmp/CAMPO_Logo-white.png on the server
 * (or already in the media library).
 */

if (!defined('ABSPATH')) {
	exit(1);
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

function as_find_attachment_by_basename($basename) {
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
	return $found ? (int) $found[0] : 0;
}

function as_sideload_local_or_url($source, $parent_id, $title, $basename = '') {
	if ($source === '') {
		return 0;
	}
	if (!$basename) {
		$basename = basename(parse_url($source, PHP_URL_PATH) ?: $source);
	}
	$existing = as_find_attachment_by_basename($basename);
	if ($existing) {
		return $existing;
	}

	if (str_starts_with($source, '/') && is_readable($source)) {
		$tmp = wp_tempnam($basename);
		if (!@copy($source, $tmp)) {
			echo "  copy failed: {$source}\n";
			return 0;
		}
	} elseif (str_starts_with($source, 'http')) {
		$tmp = download_url($source);
		if (is_wp_error($tmp)) {
			echo '  download failed: ' . $tmp->get_error_message() . "\n";
			return 0;
		}
	} else {
		echo "  unknown source: {$source}\n";
		return 0;
	}

	$id = media_handle_sideload([
		'name'     => $basename,
		'tmp_name' => $tmp,
	], $parent_id, $title);
	if (is_wp_error($id)) {
		@unlink($tmp);
		echo '  sideload failed: ' . $id->get_error_message() . "\n";
		return 0;
	}
	return (int) $id;
}

function as_upsert_news_from_json($path, $thumb_override = null) {
	$data = json_decode((string) file_get_contents($path), true);
	if (!$data || empty($data['slug'])) {
		echo "Invalid JSON: {$path}\n";
		return 0;
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
		return 0;
	}

	delete_post_meta($id, '_et_pb_use_builder');
	delete_post_meta($id, '_et_builder_version');
	update_post_meta($id, '_et_pb_page_layout', 'et_no_sidebar');
	update_post_meta($id, '_et_pb_side_nav', 'off');
	update_post_meta($id, '_et_pb_show_title', 'on');

	$thumb_id = 0;
	if (!empty($data['thumb_attachment_id'])) {
		$thumb_id = (int) $data['thumb_attachment_id'];
	} elseif ($thumb_override) {
		$thumb_id = as_sideload_local_or_url($thumb_override['source'], $id, $data['title'], $thumb_override['basename'] ?? '');
	} elseif (!empty($data['thumb_url']) && str_starts_with($data['thumb_url'], 'http')) {
		$thumb_id = as_sideload_local_or_url($data['thumb_url'], $id, $data['title']);
	}

	if ($thumb_id) {
		set_post_thumbnail($id, $thumb_id);
		if (!empty($data['thumb_alt'])) {
			update_post_meta($thumb_id, '_wp_attachment_image_alt', $data['thumb_alt']);
		}
	}

	echo "{$label} {$slug} (#{$id}) thumb=#{$thumb_id} " . get_permalink($id) . "\n";
	return (int) $id;
}

$dir = __DIR__ . '/news-export';

// Ensure CA-MPO white logo is in media for in-body use.
$logo_id = as_sideload_local_or_url(
	file_exists('/tmp/CAMPO_Logo-white.png') ? '/tmp/CAMPO_Logo-white.png' : 'https://ca-mpo.org/wp-content/uploads/CAMPO_Logo-white.png',
	0,
	'CA-MPO logo',
	'CAMPO_Logo-white.png'
);
echo "CA-MPO white logo attachment #{$logo_id}\n";

// MPO post: hero = forest green + logo composite; no vehicle photos.
$mpo_id = as_upsert_news_from_json($dir . '/post-mpo-paratransit-vehicle-funding.json', [
	'source'   => file_exists('/tmp/CA-MPO-logo-hero.jpg') ? '/tmp/CA-MPO-logo-hero.jpg' : '',
	'basename' => 'CA-MPO-logo-hero.jpg',
]);
if ($mpo_id && !get_post_thumbnail_id($mpo_id) && $logo_id) {
	set_post_thumbnail($mpo_id, $logo_id);
	echo "MPO fell back to white logo thumb\n";
}

as_upsert_news_from_json($dir . '/post-minivan-arrival.json');
as_upsert_news_from_json($dir . '/post-bus-arrival.json');

if (function_exists('wp_cache_flush')) {
	wp_cache_flush();
}
echo "Done.\n";
