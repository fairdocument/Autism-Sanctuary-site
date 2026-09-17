<?php
/**
 * Audit all published pages on Autism Sanctuary for pasted HTML vs native Divi components.
 * Run: wp eval-file wordpress-migration/audit-site-pages.php
 */

if (!defined('ABSPATH')) {
    exit(1);
}

$pages = get_posts([
    'post_type'      => 'page',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => 'menu_order title',
    'order'          => 'ASC',
]);

echo "================================================================================\n";
echo "AUTISM SANCTUARY: SITE PAGE AUDIT - PASTED HTML VS NATIVE DIVI COMPONENTS\n";
echo "================================================================================\n\n";

$summary_table = [];

foreach ($pages as $page) {
    $slug = $page->post_name;
    $id = $page->ID;
    $title = $page->post_title;
    $content = $page->post_content;
    $use_builder = get_post_meta($id, '_et_pb_use_builder', true);
    $builder_version = get_post_meta($id, '_et_builder_version', true);

    // Divi 5 blocks
    preg_match_all('/<!-- wp:divi\/([a-z0-9_-]+)/i', $content, $d5_matches);
    $d5_counts = array_count_values($d5_matches[1]);

    // Legacy shortcodes
    preg_match_all('/\[et_pb_([a-z0-9_-]+)/i', $content, $sc_matches);
    $sc_counts = array_count_values($sc_matches[1]);

    // Check module types
    $code_count = ($d5_counts['code'] ?? 0) + ($sc_counts['code'] ?? 0);
    $text_count = ($d5_counts['text'] ?? 0) + ($sc_counts['text'] ?? 0);
    $image_count = ($d5_counts['image'] ?? 0) + ($sc_counts['image'] ?? 0);
    $button_count = ($d5_counts['button'] ?? 0) + ($sc_counts['button'] ?? 0);
    $blog_count = ($d5_counts['blog'] ?? 0) + ($sc_counts['blog'] ?? 0);
    $other_native = 0;
    foreach (array_merge($d5_counts, $sc_counts) as $mod => $cnt) {
        if (!in_array($mod, ['section', 'row', 'column', 'code', 'text', 'image', 'button', 'blog'])) {
            $other_native += $cnt;
        }
    }

    // HTML patterns inside content
    $has_raw_section = (bool) preg_match('/<section\b/i', $content);
    $has_raw_div = (bool) preg_match('/<div\b/i', $content);
    $has_as_hero = (bool) preg_match('/\bas-hero\b/i', $content);
    $has_as_banner = (bool) preg_match('/\bas-banner\b/i', $content);
    $has_as_split = (bool) preg_match('/\bas-split\b/i', $content);
    $has_as_card = (bool) preg_match('/\bas-card\b/i', $content);
    $has_as_checklist = (bool) preg_match('/\bas-checklist\b/i', $content);
    $has_as_btn = (bool) preg_match('/\bas-btn\b/i', $content);
    $has_gravityform = (bool) preg_match('/\[gravityform\b|<form\b/i', $content);
    $has_raw_img = (bool) preg_match('/<img\b/i', $content);
    $has_img_placeholder = (bool) preg_match('/as-img-placeholder/i', $content);

    // Extract text vs html size
    $plain_text = strip_tags($content);
    $total_len = strlen($content);
    $text_len = strlen($plain_text);
    $html_ratio = $total_len > 0 ? round((1 - ($text_len / $total_len)) * 100) : 0;

    // Determine architectural state
    $state = 'Unknown';
    if ($code_count > 0 && $text_count == 0 && $image_count == 0) {
        $state = '100% Raw HTML in Divi Code Modules';
    } elseif ($code_count > 0) {
        $state = 'Mixed (Code + Text/Image modules with pasted HTML)';
    } elseif ($text_count > 0 && ($has_raw_div || $has_raw_section)) {
        $state = 'Pseudo-native (Divi Text modules containing raw HTML structures)';
    } elseif ($text_count > 0 || $image_count > 0) {
        $state = 'Mostly Native Divi';
    }

    echo "--------------------------------------------------------------------------------\n";
    echo "PAGE: {$title} (/{$slug}/) - ID: {$id}\n";
    echo "--------------------------------------------------------------------------------\n";
    echo "Builder: " . ($use_builder ?: 'OFF') . " | Divi Version: {$builder_version}\n";
    echo "Divi Blocks (D5): " . ($d5_counts ? json_encode($d5_counts) : 'None') . "\n";
    echo "Legacy Shortcodes: " . ($sc_counts ? json_encode($sc_counts) : 'None') . "\n";
    echo "Key Modules: Code={$code_count}, Text={$text_count}, Image={$image_count}, Button={$button_count}, Blog={$blog_count}\n";
    echo "HTML Elements Embedded:\n";
    echo "  - Raw <section> tags: " . ($has_raw_section ? 'YES' : 'No') . "\n";
    echo "  - Raw <div> wrappers: " . ($has_raw_div ? 'YES' : 'No') . "\n";
    echo "  - Hero component (.as-hero): " . ($has_as_hero ? 'YES' : 'No') . "\n";
    echo "  - Banner component (.as-banner): " . ($has_as_banner ? 'YES' : 'No') . "\n";
    echo "  - Split layout (.as-split): " . ($has_as_split ? 'YES' : 'No') . "\n";
    echo "  - Cards (.as-card): " . ($has_as_card ? 'YES' : 'No') . "\n";
    echo "  - Checklist (.as-checklist): " . ($has_as_checklist ? 'YES' : 'No') . "\n";
    echo "  - Hardcoded buttons (.as-btn): " . ($has_as_btn ? 'YES' : 'No') . "\n";
    echo "  - Raw <img> tags: " . ($has_raw_img ? 'YES' : 'No') . "\n";
    echo "  - Image placeholders: " . ($has_img_placeholder ? 'YES' : 'No') . "\n";
    echo "  - Gravity Form: " . ($has_gravityform ? 'YES' : 'No') . "\n";
    echo "Architecture Status: {$state}\n\n";

    $summary_table[] = [
        'slug' => $slug,
        'title' => $title,
        'id' => $id,
        'code_mods' => $code_count,
        'text_mods' => $text_count,
        'img_mods' => $image_count,
        'btn_mods' => $button_count,
        'status' => $state,
    ];
}

echo "\n================================================================================\n";
echo "SUMMARY MATRIX\n";
echo "================================================================================\n";
printf("%-18s | %-6s | %-6s | %-6s | %-6s | %s\n", "Slug", "Code", "Text", "Image", "Button", "Architecture Status");
echo str_repeat('-', 85) . "\n";
foreach ($summary_table as $row) {
    printf("%-18s | %-6d | %-6d | %-6d | %-6d | %s\n",
        $row['slug'],
        $row['code_mods'],
        $row['text_mods'],
        $row['img_mods'],
        $row['btn_mods'],
        $row['status']
    );
}
echo "================================================================================\n";
