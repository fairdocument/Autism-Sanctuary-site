<?php
/**
 * Archive old home/about/people and promote updated-* pages to canonical URLs.
 */

if (!defined('ABSPATH')) {
	exit(1);
}

$pairs = [
	// [old_id, updated_id, canonical_slug, archive_slug, canonical_title]
	[25, 409, 'home', 'home-archived', 'Home'],
	[26, 416, 'about', 'about-archived', 'About'],
	[30, 419, 'people', 'people-archived', 'People'],
];

$backup_dir = WP_CONTENT_DIR . '/as-promote-backup-' . gmdate('Ymd-His');
wp_mkdir_p($backup_dir);

foreach ($pairs as [$old_id, $new_id, $slug, $archive_slug, $title]) {
	$old = get_post($old_id);
	$new = get_post($new_id);
	if (!$old || !$new) {
		echo "MISS old=$old_id new=$new_id\n";
		continue;
	}

	file_put_contents("$backup_dir/{$old_id}-{$old->post_name}.html", $old->post_content);
	file_put_contents("$backup_dir/{$new_id}-{$new->post_name}.html", $new->post_content);

	// Free the canonical slug: move old page to archive + draft.
	wp_update_post([
		'ID'          => $old_id,
		'post_name'   => $archive_slug,
		'post_title'  => $old->post_title . ' (archived)',
		'post_status' => 'draft',
	]);

	// Promote updated page into canonical slug/title/publish.
	wp_update_post([
		'ID'          => $new_id,
		'post_name'   => $slug,
		'post_title'  => $title,
		'post_status' => 'publish',
	]);

	update_post_meta($new_id, '_et_pb_use_builder', 'on');
	update_post_meta($new_id, '_et_builder_version', '5.0.0');

	echo "OK archived #$old_id → /$archive_slug/ (draft); promoted #$new_id → /$slug/\n";
}

// Front page → new home (409).
update_option('show_on_front', 'page');
update_option('page_on_front', 409);
echo "Front page set to #409\n";

// Remap nav menu items that pointed at archived pages.
$map = [
	25 => 409,
	26 => 416,
	30 => 419,
];
$menus = wp_get_nav_menus();
foreach ($menus as $menu) {
	$items = wp_get_nav_menu_items($menu->term_id, ['post_status' => 'any']);
	if (!$items) {
		continue;
	}
	foreach ($items as $item) {
		if ($item->type !== 'post_type' || $item->object !== 'page') {
			continue;
		}
		$object_id = (int) $item->object_id;
		if (!isset($map[$object_id])) {
			continue;
		}
		$new_id = $map[$object_id];
		update_post_meta($item->ID, '_menu_item_object_id', $new_id);
		wp_update_post([
			'ID'         => $item->ID,
			'post_title' => '',
		]);
		echo "Menu '{$menu->name}' item #{$item->ID}: page $object_id → $new_id\n";
	}
}

// Flush caches / rewrites.
flush_rewrite_rules(false);
if (function_exists('et_core_clear_wp_cache')) {
	et_core_clear_wp_cache();
}
wp_cache_flush();

echo "Backup: $backup_dir\n";
echo "DONE\n";
