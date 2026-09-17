<?php
/**
 * Setup and activate Autism Sanctuary Divi Child Theme.
 * Run: wp eval-file wordpress-migration/setup-child-theme.php
 */

if (!defined('ABSPATH')) {
    exit(1);
}

$child_slug = 'autismsanctuary-child';
$child_dir = get_theme_root() . '/' . $child_slug;

echo "=== Autism Sanctuary Child Theme Setup ===\n";

// 1. Create directory if not exists
if (!file_exists($child_dir)) {
    wp_mkdir_p($child_dir);
    echo "Created theme directory: {$child_dir}\n";
}

// 2. Ensure style.css exists
$style_content = <<<CSS
/*
Theme Name: Autism Sanctuary Child
Theme URI: https://autismsanctuary.org/
Description: Divi Child Theme for Autism Sanctuary.
Author: Autism Sanctuary
Author URI: https://autismsanctuary.org/
Template: Divi
Version: 1.0.0
Text Domain: autismsanctuary-child
*/

/* Child theme specific overrides can be added below */
CSS;
file_put_contents($child_dir . '/style.css', $style_content);
echo "Written: {$child_dir}/style.css\n";

// 3. Ensure functions.php exists
$functions_content = <<<PHP
<?php
/**
 * Autism Sanctuary Child Theme functions and definitions.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue parent and child theme stylesheets.
 */
add_action('wp_enqueue_scripts', function () {
    \$parent_version = function_exists('et_get_theme_version') ? et_get_theme_version() : '5.11.1';
    
    wp_enqueue_style(
        'divi-parent-style',
        get_template_directory_uri() . '/style.css',
        [],
        \$parent_version
    );

    wp_enqueue_style(
        'autismsanctuary-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        ['divi-parent-style'],
        wp_get_theme()->get('Version')
    );
}, 20);
PHP;
file_put_contents($child_dir . '/functions.php', $functions_content);
echo "Written: {$child_dir}/functions.php\n";

// 4. Migrate Theme Mods from Divi to autismsanctuary-child
$parent_mods = get_option('theme_mods_Divi');
if ($parent_mods && is_array($parent_mods)) {
    update_option('theme_mods_' . $child_slug, $parent_mods);
    echo "Copied " . count($parent_mods) . " theme mods from theme_mods_Divi to theme_mods_{$child_slug}\n";
} else {
    echo "Warning: No theme_mods_Divi found to copy.\n";
}

// 5. Switch active theme
switch_theme($child_slug);

// 6. Verify
$active_sheet = get_stylesheet();
$active_template = get_template();
$active_mods = get_theme_mods();

echo "\n--- Verification ---\n";
echo "Active Stylesheet: {$active_sheet}\n";
echo "Active Template (Parent): {$active_template}\n";
echo "Theme Mods Count: " . (is_array($active_mods) ? count($active_mods) : 0) . "\n";
echo "Primary Menu ID: " . ($active_mods['nav_menu_locations']['primary-menu'] ?? 'MISSING') . "\n";
echo "Footer Menu ID: " . ($active_mods['nav_menu_locations']['footer-menu'] ?? 'MISSING') . "\n";
echo "Custom Logo ID: " . ($active_mods['custom_logo'] ?? 'MISSING') . "\n";

if ($active_sheet === $child_slug && $active_template === 'Divi') {
    echo "\nSUCCESS: Child theme {$child_slug} is active and properly inherits from Divi.\n";
} else {
    echo "\nERROR: Child theme activation failed verification.\n";
}
