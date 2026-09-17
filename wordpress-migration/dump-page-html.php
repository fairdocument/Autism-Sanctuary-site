<?php
/**
 * Detailed extract of all HTML fragments within Divi modules on key pages.
 */

if (!defined('ABSPATH')) {
    exit(1);
}

$slugs = ['home', 'about', 'people', 'programs', 'our-farm', 'resources', 'careers', 'donate', 'contact', 'thanks', 'privacy', 'terms', 'news'];

foreach ($slugs as $slug) {
    $page = get_page_by_path($slug);
    if (!$page) continue;

    echo "================================================================================\n";
    echo "PAGE: {$page->post_title} (/{$slug}/)\n";
    echo "================================================================================\n";

    $blocks = parse_blocks($page->post_content);
    
    $block_num = 0;
    $extract_html = function($blocks, $parent = '') use (&$extract_html, &$block_num) {
        foreach ($blocks as $b) {
            $name = $b['blockName'] ?? '';
            $classes = $b['attrs']['module']['advanced']['htmlAttributes']['desktop']['value']['class'] ?? ($b['attrs']['moduleClass'] ?? '');
            
            $val = '';
            if (isset($b['attrs']['content']['innerContent']['desktop']['value'])) {
                $val = $b['attrs']['content']['innerContent']['desktop']['value'];
            } elseif (isset($b['attrs']['rawContent'])) {
                $val = $b['attrs']['rawContent'];
            }

            if (is_string($val) && (strpos($val, '\u003c') !== false || strpos($val, 'u003c') !== false)) {
                $decoded = json_decode('"' . $val . '"');
                if ($decoded) $val = $decoded;
            }

            if (is_string($val) && (strpos($val, '<') !== false || $name === 'divi/code')) {
                $block_num++;
                echo "\n--- Module #{$block_num}: [{$name}] " . ($classes ? "Class: {$classes}" : "") . " ---\n";
                echo trim($val) . "\n";
            }

            if (!empty($b['innerBlocks'])) {
                $extract_html($b['innerBlocks'], $name);
            }
        }
    };

    $extract_html($blocks);
    echo "\n\n";
}
