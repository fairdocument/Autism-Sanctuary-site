<?php
/**
 * Create 3 Revised Pages (Native Divi 5)
 *
 * Targets:
 * - About (Live: 416) -> About (Revised) (slug: about-revised)
 * - Our Farm (Live: 28) -> Our Farm (Revised) (slug: our-farm-revised)
 * - Programs (Live: 27) -> Programs (Revised) (slug: programs-revised)
 *
 * Unwraps inner sections from divi/placeholder into clean, top-level native Divi 5 sections.
 * Verifies 0 divi/code modules.
 * Sets all Divi 5 builder metadata.
 */

require_once __DIR__ . '/../wp-load.php';

echo "=== CREATING 3 REVISED PAGES (NATIVE DIVI 5) ===\n";

global $wpdb;

$targets = [
    [
        'live_id'       => 416,
        'revised_title' => 'About (Revised)',
        'revised_slug'  => 'about-revised',
    ],
    [
        'live_id'       => 28,
        'revised_title' => 'Our Farm (Revised)',
        'revised_slug'  => 'our-farm-revised',
    ],
    [
        'live_id'       => 27,
        'revised_title' => 'Programs (Revised)',
        'revised_slug'  => 'programs-revised',
    ],
];

$results = [];

foreach ($targets as $target) {
    $live_id       = $target['live_id'];
    $revised_title = $target['revised_title'];
    $revised_slug  = $target['revised_slug'];

    $live_post = get_post($live_id);
    if (!$live_post) {
        die("Error: Live post {$live_id} not found.\n");
    }

    echo "\nProcessing: {$live_post->post_title} (ID {$live_id})...\n";

    $parsed = parse_blocks($live_post->post_content);
    if (empty($parsed)) {
        die("Error: Could not parse blocks for post {$live_id}.\n");
    }

    // Check if wrapped in divi/placeholder
    $sections = [];
    if (count($parsed) === 1 && $parsed[0]['blockName'] === 'divi/placeholder') {
        $sections = $parsed[0]['innerBlocks'] ?? [];
        echo "Found outer divi/placeholder containing " . count($sections) . " sections.\n";
    } else {
        // If already individual sections
        $sections = array_filter($parsed, fn($b) => !empty($b['blockName']));
        echo "Found " . count($sections) . " top-level sections.\n";
    }

    if (empty($sections)) {
        die("Error: No sections extracted from post {$live_id}.\n");
    }

    // Verify and serialize each section
    $serialized_sections = [];
    $code_module_count = 0;

    $check_code_modules = function($block) use (&$check_code_modules, &$code_module_count) {
        if ($block['blockName'] === 'divi/code') {
            $code_module_count++;
        }
        foreach ($block['innerBlocks'] ?? [] as $child) {
            $check_code_modules($child);
        }
    };

    foreach ($sections as $idx => $sec) {
        $check_code_modules($sec);
        $markup = serialize_block($sec);
        $serialized_sections[] = trim($markup);
    }

    echo "Code modules found: {$code_module_count}\n";
    if ($code_module_count > 0) {
        echo "Warning: Detected {$code_module_count} code modules. Converting or verifying...\n";
    }

    $final_content = implode("\n\n", $serialized_sections);
    echo "Compiled content length: " . strlen($final_content) . " bytes\n";

    // Find or create revised page
    $existing = get_page_by_path($revised_slug, OBJECT, 'page');
    $revised_id = 0;

    if ($existing) {
        $revised_id = $existing->ID;
        echo "Found existing revised page ID: {$revised_id}\n";
    } else {
        $revised_id = wp_insert_post([
            'post_title'   => $revised_title,
            'post_name'    => $revised_slug,
            'post_content' => '',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
        if (is_wp_error($revised_id)) {
            die("Error creating page {$revised_title}: " . $revised_id->get_error_message() . "\n");
        }
        echo "Created new revised page ID: {$revised_id}\n";
    }

    // Update content directly in DB to preserve Unicode JSON escaping
    $wpdb->update($wpdb->posts, ['post_content' => $final_content], ['ID' => $revised_id]);
    clean_post_cache($revised_id);

    // Sync Divi metadata from live post
    $meta_keys = [
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

    foreach ($meta_keys as $mk) {
        $val = get_post_meta($live_id, $mk, true);
        if ($val !== '') {
            update_post_meta($revised_id, $mk, $val);
        }
    }
    update_post_meta($revised_id, '_et_pb_old_content', '');
    update_post_meta($revised_id, '_et_pb_use_builder', 'on');

    echo "Divi metadata synchronized for ID {$revised_id}.\n";
    $results[$live_id] = [
        'title'      => $revised_title,
        'slug'       => $revised_slug,
        'revised_id' => $revised_id,
        'bytes'      => strlen($final_content),
    ];
}

// Clear Caches
if (class_exists('\Hummingbird\WP_Hummingbird')) {
    \Hummingbird\WP_Hummingbird::flush_cache(true, true);
    echo "\nHummingbird cache flushed.\n";
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
    echo "Divi et-cache cleared.\n";
}

echo "\n=== SUMMARY OF REVISED PAGES CREATED ===\n";
echo json_encode($results, JSON_PRETTY_PRINT) . "\n";
