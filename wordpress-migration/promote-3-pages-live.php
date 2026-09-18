<?php
/**
 * Promote About, Our Farm, and Programs to Live (Native Divi 5)
 *
 * Targets:
 * - About: Source 1179 -> Dest 416
 * - Our Farm: Source 1180 -> Dest 28
 * - Programs: Source 1181 -> Dest 27
 *
 * For each:
 * 1. Archives existing destination content into a dedicated WordPress draft page and post meta.
 * 2. Copies 100% native Divi 5 post_content and Divi metadata to destination post.
 * 3. Cleans post caches, Divi static caches, and Hummingbird page caches.
 */

require_once __DIR__ . '/../wp-load.php';

echo "=== PROMOTING ABOUT, OUR FARM, AND PROGRAMS TO LIVE ===\n";

global $wpdb;

$promotions = [
    [
        'name'      => 'About',
        'source_id' => 1179,
        'dest_id'   => 416,
        'slug'      => 'about',
    ],
    [
        'name'      => 'Our Farm',
        'source_id' => 1180,
        'dest_id'   => 28,
        'slug'      => 'our-farm',
    ],
    [
        'name'      => 'Programs',
        'source_id' => 1181,
        'dest_id'   => 27,
        'slug'      => 'programs',
    ],
];

$meta_keys_to_sync = [
    '_et_pb_use_builder',
    '_et_pb_page_layout',
    '_et_pb_show_title',
    '_et_builder_version',
    '_et_pb_built_for_post_type',
    '_divi_dynamic_assets_cached_modules',
    '_divi_dynamic_assets_canvases_used',
    '_divi_dynamic_assets_cached_feature_used',
    'et_enqueued_post_fonts',
    '_et_builder_post_features_cache',
];

foreach ($promotions as $promo) {
    $name      = $promo['name'];
    $source_id = $promo['source_id'];
    $dest_id   = $promo['dest_id'];
    $slug      = $promo['slug'];

    $source = get_post($source_id);
    $dest   = get_post($dest_id);

    if (!$source || empty($source->post_content)) {
        die("Error: Source {$name} (ID {$source_id}) not found or empty.\n");
    }
    if (!$dest) {
        die("Error: Destination {$name} (ID {$dest_id}) not found.\n");
    }

    echo "\n--- Promoting {$name} (ID {$dest_id}) ---\n";
    echo "Source content length: " . strlen($source->post_content) . " bytes\n";
    echo "Destination prior length: " . strlen($dest->post_content) . " bytes\n";

    // 1. Create Archive Page for Existing Content
    $archive_title = "{$name} (archived " . date('Y-m-d H:i') . ")";
    $archive_slug  = "{$slug}-archived-" . date('Ymd-His');

    $archived_post_id = wp_insert_post([
        'post_title'   => $archive_title,
        'post_name'    => $archive_slug,
        'post_content' => $dest->post_content,
        'post_status'  => 'draft',
        'post_type'    => 'page',
    ]);

    if (is_wp_error($archived_post_id)) {
        die("Error creating archive post for {$name}: " . $archived_post_id->get_error_message() . "\n");
    }

    // Copy Divi metadata to archived post
    update_post_meta($archived_post_id, '_et_pb_use_builder', 'on');
    update_post_meta($archived_post_id, '_et_pb_page_layout', 'et_no_sidebar');
    update_post_meta($archived_post_id, '_et_pb_show_title', 'off');
    update_post_meta($archived_post_id, '_et_builder_version', get_post_meta($dest_id, '_et_builder_version', true));

    echo "Created archive page: ID {$archived_post_id} ('{$archive_title}')\n";

    // 2. Save backup post meta on destination post
    update_post_meta($dest_id, '_as_backup_pre_native_divi5', [
        'time'             => current_time('mysql'),
        'archived_post_id' => $archived_post_id,
        'post_content'     => $dest->post_content,
    ]);
    echo "Backup post meta saved on ID {$dest_id}.\n";

    // 3. Update destination post content directly in DB
    $wpdb->update($wpdb->posts, ['post_content' => $source->post_content], ['ID' => $dest_id]);
    clean_post_cache($dest_id);
    echo "Updated ID {$dest_id} post_content with native Divi 5 blocks.\n";

    // 4. Sync Divi 5 metadata
    foreach ($meta_keys_to_sync as $mk) {
        $val = get_post_meta($source_id, $mk, true);
        if ($val !== '') {
            update_post_meta($dest_id, $mk, $val);
        }
    }
    update_post_meta($dest_id, '_et_pb_old_content', '');
    update_post_meta($dest_id, '_et_pb_use_builder', 'on');
    echo "Synchronized Divi metadata for ID {$dest_id}.\n";
}

// 5. Clear Caches
echo "\n--- Clearing All Caches ---\n";
if (class_exists('\Hummingbird\WP_Hummingbird')) {
    \Hummingbird\WP_Hummingbird::flush_cache(true, true);
    echo "Hummingbird cache flushed.\n";
}

wp_cache_flush();
echo "WordPress object cache flushed.\n";

$et_cache_dir = WP_CONTENT_DIR . '/et-cache';
if (is_dir($et_cache_dir)) {
    $files = glob($et_cache_dir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }
    echo "Divi et-cache files cleared.\n";
}

echo "\n=== SUCCESS: About, Our Farm, and Programs are now 100% Native Divi 5 LIVE! ===\n";
