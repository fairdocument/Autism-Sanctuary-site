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
    $parent_version = function_exists('et_get_theme_version') ? et_get_theme_version() : '5.11.1';
    
    wp_enqueue_style(
        'divi-parent-style',
        get_template_directory_uri() . '/style.css',
        [],
        $parent_version
    );

    wp_enqueue_style(
        'autismsanctuary-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        ['divi-parent-style'],
        wp_get_theme()->get('Version')
    );
}, 20);
