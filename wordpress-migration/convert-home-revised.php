<?php
/**
 * Convert Home Revised (ID 692) to 100% Native Divi 5 Modules
 *
 * Uses the complete, native Divi 5 section hierarchy from post 532:
 * - Section 0: Fullwidth Hero with video background + fallback + Cormorant Garamond typography + native buttons
 * - Section 1: Our Approach (2-column flex row with native Image module + prose text)
 * - Section 2: Our Services (2-column editorial list with green dividers)
 * - Section 3: Our Home (2-column flex row with Edgefield history + native buttons + aerial farmhouse photo)
 * - Section 4: Looking Ahead (2-column flex row with pasture photo + checklist + CTA)
 * - Section 5: Newsroom (native Divi 5 Blog module for 3 recent posts + native Divi text link)
 * - Section 6: Get In Touch (native Divi 5 Section, Row, Column, Text CTA with Gold & Cream buttons)
 *
 * ZERO raw HTML code modules in the body.
 * Strictly scoped to Page 692 (/home-revised/). Never modifies live ID 532.
 */

if (!defined('ABSPATH')) {
    require_once dirname(__DIR__) . '/wp-load.php';
}

echo "=== UPDATING HOME REVISED (PAGE 692) WITH 100% NATIVE DIVI 5 SECTIONS ===\n";

global $wpdb;

$live_home = get_post(532);
if (!$live_home) {
    die("Error: Live home post 532 not found.\n");
}

$revised_home = get_post(692);
if (!$revised_home) {
    die("Error: Revised home post 692 not found.\n");
}

$parsed_532 = parse_blocks($live_home->post_content);
if (empty($parsed_532[0]['innerBlocks']) || count($parsed_532[0]['innerBlocks']) < 7) {
    die("Error: Could not extract 7 sections from post 532.\n");
}

$sections = $parsed_532[0]['innerBlocks'];
echo "Found " . count($sections) . " sections in post 532.\n";

// Ensure Section 5 Mod 2 is native divi/text instead of divi/code
if (isset($sections[5]['innerBlocks'][0]['innerBlocks'][0]['innerBlocks'][2])) {
    $mod2 = &$sections[5]['innerBlocks'][0]['innerBlocks'][0]['innerBlocks'][2];
    if ($mod2['blockName'] === 'divi/code') {
        $mod2['blockName'] = 'divi/text';
        echo "Section 5: Converted Mod 2 from divi/code to native divi/text.\n";
    }
}

// Serialize each section into individual top-level native Divi 5 sections
$serialized_sections = [];
foreach ($sections as $idx => $sec) {
    $markup = serialize_block($sec);
    $serialized_sections[] = trim($markup);
    echo "Section $idx (" . $sec['blockName'] . ") serialized: " . strlen($markup) . " bytes\n";
}

$final_content = implode("\n\n", $serialized_sections);
echo "Total final content length: " . strlen($final_content) . " bytes\n";

// Update Page 692 in Database
$wpdb->update($wpdb->posts, ['post_content' => $final_content], ['ID' => 692]);
clean_post_cache(692);

update_post_meta(692, '_et_pb_use_builder', 'on');
update_post_meta(692, '_et_pb_built_for_post_type', 'page');
update_post_meta(692, '_et_builder_version', '5.11.1');

// Flush caches
if (class_exists('\Hummingbird\WP_Hummingbird')) {
    \Hummingbird\WP_Hummingbird::flush_cache(true, true);
    echo "Hummingbird cache flushed.\n";
}
wp_cache_flush();
echo "WordPress object cache flushed.\n";

echo "=== SUCCESS: Page 692 (Home Revised) 100% Native Divi 5 update complete! ===\n";
