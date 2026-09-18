<?php
/**
 * Unwrap 5 Utility Pages (Native Divi 5)
 *
 * Targets:
 * - Resources (ID 31)
 * - Careers & volunteers (ID 32)
 * - Donate (ID 34)
 * - Privacy (ID 37)
 * - Terms (ID 38)
 *
 * Extracts inner sections from divi/placeholder into top-level native Divi 5 sections.
 * Safely creates draft archive backups and post meta backups.
 * Flushes all caches.
 */

require_once __DIR__ . '/../wp-load.php';

echo "=== UNWRAPPING 5 UTILITY PAGES (NATIVE DIVI 5) ===\n";

global $wpdb;

$target_ids = [31, 32, 34, 37, 38];
$results = [];

foreach ($target_ids as $id) {
    $post = get_post($id);
    if (!$post) {
        die("Error: Post ID {$id} not found.\n");
    }

    echo "\nProcessing: {$post->post_title} (ID {$id}, /{$post->post_name}/)...\n";

    $parsed = parse_blocks($post->post_content);
    if (empty($parsed)) {
        die("Error: Could not parse blocks for post {$id}.\n");
    }

    // Extract sections from divi/placeholder
    $sections = [];
    if (count($parsed) === 1 && $parsed[0]['blockName'] === 'divi/placeholder') {
        $sections = $parsed[0]['innerBlocks'] ?? [];
        echo "Extracted " . count($sections) . " sections from divi/placeholder.\n";
    } else {
        // Filter valid blocks
        $sections = array_filter($parsed, fn($b) => !empty($b['blockName']));
        echo "Found " . count($sections) . " existing blocks.\n";
    }

    if (empty($sections)) {
        die("Error: No sections found in post {$id}.\n");
    }

    // Verify zero code modules
    $code_count = 0;
    $check_code = function($b) use (&$check_code, &$code_count) {
        if ($b['blockName'] === 'divi/code') $code_count++;
        foreach ($b['innerBlocks'] ?? [] as $child) $check_code($child);
    };

    $serialized_sections = [];
    foreach ($sections as $sec) {
        $check_code($sec);
        $serialized_sections[] = trim(serialize_block($sec));
    }

    if ($code_count > 0) {
        echo "Warning: {$code_count} code modules found.\n";
    } else {
        echo "Verified 0 code modules.\n";
    }

    $final_content = implode("\n\n", $serialized_sections);

    // 1. Create safety archive draft
    $archive_title = "{$post->post_title} (archived " . date('Y-m-d H:i') . ")";
    $archive_slug  = "{$post->post_name}-archived-" . date('Ymd-His');

    $archived_id = wp_insert_post([
        'post_title'   => $archive_title,
        'post_name'    => $archive_slug,
        'post_content' => $post->post_content,
        'post_status'  => 'draft',
        'post_type'    => 'page',
    ]);

    if (!is_wp_error($archived_id)) {
        update_post_meta($archived_id, '_et_pb_use_builder', 'on');
        echo "Created safety archive draft: ID {$archived_id} ('{$archive_title}')\n";
    }

    // 2. Save backup post meta on target post
    update_post_meta($id, '_as_backup_pre_native_divi5', [
        'time'             => current_time('mysql'),
        'archived_post_id' => $archived_id,
        'post_content'     => $post->post_content,
    ]);

    // 3. Update target post content directly via DB
    $wpdb->update($wpdb->posts, ['post_content' => $final_content], ['ID' => $id]);
    clean_post_cache($id);

    // 4. Update Divi flags
    update_post_meta($id, '_et_pb_use_builder', 'on');
    update_post_meta($id, '_et_pb_old_content', '');
    update_post_meta($id, '_et_builder_version', '5.11.1');
    update_post_meta($id, '_et_pb_page_layout', 'et_no_sidebar');
    update_post_meta($id, '_et_pb_show_title', 'off');

    echo "Post {$id} successfully updated to top-level native Divi 5 sections (" . strlen($final_content) . " bytes).\n";

    $results[$id] = [
        'title'       => $post->post_title,
        'slug'        => $post->post_name,
        'sections'    => count($sections),
        'archived_id' => $archived_id,
        'bytes'       => strlen($final_content),
    ];
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

echo "\n=== UNWRAPPING COMPLETE SUMMARY ===\n";
echo json_encode($results, JSON_PRETTY_PRINT) . "\n";
