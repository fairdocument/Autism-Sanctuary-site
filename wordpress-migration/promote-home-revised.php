<?php
/**
 * Promote Revised Homepage (Post 692) to Live (Post 532)
 *
 * 1. Archives existing Post 532 content into a dedicated WordPress draft page and post meta.
 * 2. Copies 100% native Divi 5 post_content and Divi metadata from Post 692 to Post 532.
 * 3. Cleans post caches, Divi static caches, and Hummingbird page caches.
 */

require_once __DIR__ . '/../wp-load.php';

echo "=== PROMOTING REVISED HOMEPAGE TO LIVE ===\n";

global $wpdb;

$source_home = get_post(692);
$dest_home   = get_post(532);

if (!$source_home || empty($source_home->post_content)) {
    die("Error: Source home (Post 692) not found or empty.\n");
}

if (!$dest_home) {
    die("Error: Destination home (Post 532) not found.\n");
}

echo "Source (692): " . strlen($source_home->post_content) . " bytes\n";
echo "Destination (532 current): " . strlen($dest_home->post_content) . " bytes\n";

// 1. Create Archive Page for Existing Homepage
$archive_title = 'Home (archived ' . date('Y-m-d H:i') . ')';
$archive_slug  = 'home-archived-' . date('Ymd-His');

$archived_post_id = wp_insert_post([
    'post_title'   => $archive_title,
    'post_name'    => $archive_slug,
    'post_content' => $dest_home->post_content,
    'post_status'  => 'draft',
    'post_type'    => 'page',
]);

if (is_wp_error($archived_post_id)) {
    die("Error creating archive post: " . $archived_post_id->get_error_message() . "\n");
}

// Copy Divi metadata to archived post
update_post_meta($archived_post_id, '_et_pb_use_builder', 'on');
update_post_meta($archived_post_id, '_et_pb_page_layout', 'et_no_sidebar');
update_post_meta($archived_post_id, '_et_pb_show_title', 'off');
update_post_meta($archived_post_id, '_et_builder_version', get_post_meta(532, '_et_builder_version', true));

echo "Created archive page: ID {$archived_post_id} ('{$archive_title}')\n";

// 2. Save backup post meta on Post 532
update_post_meta(532, '_as_backup_pre_native_divi5', [
    'time'             => current_time('mysql'),
    'archived_post_id' => $archived_post_id,
    'post_content'     => $dest_home->post_content,
]);
echo "Backup post meta stored on Post 532.\n";

// 3. Update Post 532 content directly via DB to prevent wp_filter_post_kses stripping JSON escapes
$wpdb->update($wpdb->posts, ['post_content' => $source_home->post_content], ['ID' => 532]);
clean_post_cache(532);
echo "Post 532 content updated with native Divi 5 markup from Post 692.\n";

// 4. Copy/Sync Divi 5 dynamic meta keys
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

foreach ($meta_keys_to_sync as $mk) {
    $val = get_post_meta(692, $mk, true);
    if ($val !== '') {
        update_post_meta(532, $mk, $val);
    }
}
update_post_meta(532, '_et_pb_old_content', '');

echo "Post 532 Divi metadata synchronized.\n";

// 5. Clear Caches
if (class_exists('\Hummingbird\WP_Hummingbird')) {
    \Hummingbird\WP_Hummingbird::flush_cache(true, true);
    echo "Hummingbird cache flushed.\n";
}

wp_cache_flush();
echo "WordPress object cache flushed.\n";

// Remove Divi et-cache if present
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

echo "=== SUCCESS: Revised homepage successfully promoted to live (ID 532)! ===\n";
