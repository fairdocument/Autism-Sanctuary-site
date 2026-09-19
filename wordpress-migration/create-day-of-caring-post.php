<?php
/**
 * Create/update the "Day of Caring 2026" news post with primary image and photo gallery.
 * Run: wp eval-file wordpress-migration/create-day-of-caring-post.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$json_path = __DIR__ . '/news-export/post-day-of-caring-2026.json';
$data = json_decode((string) file_get_contents($json_path), true);
if (!$data) {
	echo "Invalid JSON export file: {$json_path}\n";
	return;
}

$tmp_dir = WP_CONTENT_DIR . '/uploads/day-of-caring-tmp';

$slug = $data['slug'];
$existing = get_posts([
	'name'           => $slug,
	'post_type'      => 'post',
	'post_status'    => 'any',
	'posts_per_page' => 1,
	'fields'         => 'ids',
]);

$post_id = $existing ? (int) $existing[0] : 0;

// Helper to sideload or find existing attachment by file basename
function get_or_sideload_image($filename, $title, $alt, $parent_post_id, $tmp_dir) {
	$found = get_posts([
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_query'     => [
			[
				'key'     => '_wp_attached_file',
				'value'   => $filename,
				'compare' => 'LIKE',
			],
		],
	]);

	if ($found) {
		$att_id = (int) $found[0];
		wp_update_post([
			'ID'         => $att_id,
			'post_title' => $title,
		]);
		update_post_meta($att_id, '_wp_attachment_image_alt', $alt);
		return $att_id;
	}

	$src_file = $tmp_dir . '/' . $filename;
	if (!file_exists($src_file)) {
		echo "Source image not found: {$src_file}\n";
		return 0;
	}

	// Copy to a temp location for media_handle_sideload
	$tmp_copy = wp_tempnam($filename);
	copy($src_file, $tmp_copy);

	$file_array = [
		'name'     => $filename,
		'tmp_name' => $tmp_copy,
	];

	$att_id = media_handle_sideload($file_array, $parent_post_id, $title);
	if (is_wp_error($att_id)) {
		@unlink($tmp_copy);
		echo "Sideload failed for {$filename}: " . $att_id->get_error_message() . "\n";
		return 0;
	}

	update_post_meta($att_id, '_wp_attachment_image_alt', $alt);
	echo "Sideloaded {$filename} -> attachment #{$att_id}\n";
	return $att_id;
}

// 1. Primary featured image
$primary_id = get_or_sideload_image(
	'2026DOCGroup.jpg',
	'Day of Caring 2026 UVA Volunteer Teams',
	$data['thumb_alt'],
	$post_id,
	$tmp_dir
);

// 2. Gallery photos definitions
$gallery_items = [
	[
		'file'  => '2026DOCGroupFun.jpg',
		'title' => 'UVA Day of Caring Team Fun Pose',
		'alt'   => 'UVA volunteers striking a playful pose under the Autism Sanctuary pavilion',
	],
	[
		'file'  => '2026DOCMovingTrees.jpg',
		'title' => 'Hauling Fallen Timber',
		'alt'   => 'Volunteers lifting and carrying large tree branches out of the rehabilitation field',
	],
	[
		'file'  => '2026DOCMovingTrees2.jpg',
		'title' => 'Clearing Field Debris',
		'alt'   => 'UVA volunteers working together to clear heavy logs and brush from the field',
	],
	[
		'file'  => '2026DOCMovingTrees3.jpg',
		'title' => 'Teamwork on Heavy Brush',
		'alt'   => 'Team members collaborating to remove fallen limbs during pasture rehabilitation',
	],
	[
		'file'  => 'IMG_0535.jpeg',
		'title' => 'Working in the High Tunnel',
		'alt'   => 'Volunteers preparing soil beds inside the Autism Sanctuary high tunnel',
	],
	[
		'file'  => 'IMG_0537.jpeg',
		'title' => 'Prepping High Tunnel Planting Beds',
		'alt'   => 'UVA volunteers raking and prepping garden rows for fall and winter plantings',
	],
	[
		'file'  => 'IMG_0538.jpeg',
		'title' => 'High Tunnel Soil Preparation',
		'alt'   => 'Volunteers inside the greenhouse preparing the soil for cool-season crops',
	],
	[
		'file'  => '2026DOCPettingCows.jpg',
		'title' => 'Meeting Sanctuary Cattle',
		'alt'   => 'Volunteers visiting and petting cattle over the pasture fence',
	],
	[
		'file'  => '2026DOCChristaMoo.jpg',
		'title' => 'Pasture Friendships',
		'alt'   => 'A volunteer enjoying a quiet moment interacting with a cow in the pasture',
	],
	[
		'file'  => '2026DOCAshley.jpg',
		'title' => 'Smiles on the Farm',
		'alt'   => 'A UVA volunteer smiling during the Day of Caring workday at Autism Sanctuary',
	],
	[
		'file'  => '2026DOCCM.jpg',
		'title' => 'Volunteers at Work',
		'alt'   => 'UVA team members taking a break and smiling during the outdoor workday',
	],
	[
		'file'  => '2026DOCChristaAl.jpg',
		'title' => 'Working Together in the Pasture',
		'alt'   => 'Volunteers collaborating near the pasture fence on the farm',
	],
	[
		'file'  => '2026DOCLimo.jpg',
		'title' => 'Arriving for Day of Caring',
		'alt'   => 'UVA volunteers gathered near the farm transport vehicles ready for the workday',
	],
	[
		'file'  => 'IMG_3526.jpeg',
		'title' => 'High Tunnel Bed Raking',
		'alt'   => 'Volunteer smoothing soil beds inside the high tunnel structure',
	],
	[
		'file'  => 'IMG_3531.jpeg',
		'title' => 'High Tunnel Team Effort',
		'alt'   => 'Volunteers working diligently along the high tunnel planting beds',
	],
	[
		'file'  => 'IMG_3532.jpeg',
		'title' => 'Preparing Ground for Winter Crops',
		'alt'   => 'Volunteers turning and amending earth in the greenhouse rows',
	],
	[
		'file'  => 'image002.jpg',
		'title' => 'Field Restoration in Action',
		'alt'   => 'Volunteers carrying brush across the rehabilitated pasture grounds',
	],
];

$gallery_ids = [];
foreach ($gallery_items as $item) {
	$att_id = get_or_sideload_image($item['file'], $item['title'], $item['alt'], $post_id, $tmp_dir);
	if ($att_id) {
		$gallery_ids[] = $att_id;
	}
}

$gallery_ids_str = implode(',', $gallery_ids);
echo "Gallery IDs (" . count($gallery_ids) . " photos): {$gallery_ids_str}\n";

// Replace placeholder in content
$content = str_replace('{gallery_ids}', $gallery_ids_str, $data['content']);

$args = [
	'post_title'    => $data['title'],
	'post_name'     => $slug,
	'post_content'  => $content,
	'post_excerpt'  => $data['excerpt'],
	'post_status'   => 'publish',
	'post_type'     => 'post',
	'post_date'     => $data['date'],
	'post_date_gmt' => get_gmt_from_date($data['date']),
];

if ($post_id) {
	$args['ID'] = $post_id;
	$id = wp_update_post(wp_slash($args), true);
	echo "Updated post #{$id}\n";
} else {
	$id = wp_insert_post(wp_slash($args), true);
	echo "Created post #{$id}\n";
}

if (is_wp_error($id)) {
	echo "Error saving post: " . $id->get_error_message() . "\n";
	return;
}

// Ensure Divi Theme Builder single-post template handles layout
delete_post_meta($id, '_et_pb_use_builder');
delete_post_meta($id, '_et_builder_version');
update_post_meta($id, '_et_pb_page_layout', 'et_no_sidebar');
update_post_meta($id, '_et_pb_side_nav', 'off');
update_post_meta($id, '_et_pb_show_title', 'on');

if ($primary_id) {
	set_post_thumbnail($id, $primary_id);
	echo "Set featured image #{$primary_id} on post #{$id}\n";
}

if (function_exists('wp_cache_flush')) {
	wp_cache_flush();
}

echo "Permalink: " . get_permalink($id) . "\n";
echo "Day of Caring post creation complete.\n";
