<?php
/**
 * Detailed dump of Divi blocks and their inner HTML/content across all published pages.
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

function inspect_blocks($blocks, &$results, $prefix = '') {
    foreach ($blocks as $i => $b) {
        $name = $b['blockName'] ?? 'HTML/FreeText';
        $val = '';
        if (isset($b['attrs']['content']['innerContent']['desktop']['value'])) {
            $val = $b['attrs']['content']['innerContent']['desktop']['value'];
        } elseif (isset($b['attrs']['rawContent'])) {
            $val = $b['attrs']['rawContent'];
        } elseif (!empty($b['innerHTML'])) {
            $val = $b['innerHTML'];
        }

        // Decode JSON or unicode escapes if present
        if (is_string($val) && (strpos($val, '\u003c') !== false || strpos($val, 'u003c') !== false)) {
            $decoded = json_decode('"' . $val . '"');
            if ($decoded) $val = $decoded;
        }

        $results[] = [
            'type' => $name,
            'classes' => $b['attrs']['module']['advanced']['htmlAttributes']['desktop']['value']['class'] ?? ($b['attrs']['moduleClass'] ?? ''),
            'val_preview' => is_string($val) ? mb_substr(strip_tags($val), 0, 80) : '',
            'raw_preview' => is_string($val) ? mb_substr($val, 0, 150) : '',
            'has_html_tags' => is_string($val) && preg_match('/<[a-z][\s\S]*>/i', $val),
            'has_classes' => is_string($val) && preg_match('/class=/i', $val),
            'has_inline_style' => is_string($val) && preg_match('/style=/i', $val),
            'has_grid_or_card' => is_string($val) && preg_match('/(grid|card|column|split|hero|banner|btn)/i', $val),
        ];

        if (!empty($b['innerBlocks'])) {
            inspect_blocks($b['innerBlocks'], $results, $prefix . '  ');
        }
    }
}

foreach ($pages as $p) {
    echo "================================================================================\n";
    echo "PAGE: {$p->post_title} (/{$p->post_name}/) [ID: {$p->ID}]\n";
    echo "================================================================================\n";
    $blocks = parse_blocks($p->post_content);
    $results = [];
    inspect_blocks($blocks, $results);

    $html_heavy_modules = 0;
    $clean_modules = 0;

    foreach ($results as $idx => $r) {
        if (in_array($r['type'], ['divi/section', 'divi/row', 'divi/column', 'divi/placeholder'])) {
            continue;
        }
        echo "  [#{$idx}] {$r['type']} " . ($r['classes'] ? "(class: {$r['classes']})" : "") . "\n";
        echo "      Raw: " . str_replace(["\r", "\n"], ' ', $r['raw_preview']) . "\n";
        echo "      Has HTML tags: " . ($r['has_html_tags'] ? 'YES' : 'no') . 
             " | Has class/style: " . ($r['has_classes'] || $r['has_inline_style'] ? 'YES' : 'no') . 
             " | Has layout markup: " . ($r['has_grid_or_card'] ? 'YES' : 'no') . "\n";
        
        if ($r['has_classes'] || $r['has_grid_or_card'] || $r['type'] === 'divi/code') {
            $html_heavy_modules++;
        } else {
            $clean_modules++;
        }
    }
    echo "\n  Summary for /{$p->post_name}/: {$html_heavy_modules} HTML-dependent modules, {$clean_modules} standard content modules\n\n";
}
