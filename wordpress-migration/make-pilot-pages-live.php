<?php
/**
 * Make People and Contact Pages Live (Native Divi 5)
 *
 * Copies native Divi 5 post_content from ID 690 -> ID 419 (People)
 * and ID 691 -> ID 36 (Contact), safely backing up prior content.
 */

require_once __DIR__ . '/../wp-load.php';

echo "=== MAKING PEOPLE AND CONTACT PAGES LIVE (NATIVE DIVI 5) ===\n";

global $wpdb;

// 1. Update People (ID 419) from ID 690
$source_people = get_post(690);
$dest_people   = get_post(419);

if (!$source_people || !$dest_people) {
    die("Error: Could not locate People posts (690 or 419).\n");
}

// Backup current content
update_post_meta(419, '_as_backup_pre_native_divi5', [
    'time' => current_time('mysql'),
    'post_content' => $dest_people->post_content,
]);

// Direct database update to preserve exact JSON unicode escapes in Divi 5 block markup
$wpdb->update($wpdb->posts, ['post_content' => $source_people->post_content], ['ID' => 419]);
clean_post_cache(419);

update_post_meta(419, '_et_pb_use_builder', 'on');
update_post_meta(419, '_et_pb_old_content', '');
update_post_meta(419, '_et_pb_built_for_post_type', 'page');

echo "Updated Page 419 (People) with native Divi 5 content from Page 690.\n";

// 2. Update Contact (ID 36) from ID 691
$source_contact = get_post(691);
$dest_contact   = get_post(36);

if (!$source_contact || !$dest_contact) {
    die("Error: Could not locate Contact posts (691 or 36).\n");
}

// Backup current content
update_post_meta(36, '_as_backup_pre_native_divi5', [
    'time' => current_time('mysql'),
    'post_content' => $dest_contact->post_content,
]);

// Direct database update to preserve exact JSON unicode escapes in Divi 5 block markup
$wpdb->update($wpdb->posts, ['post_content' => $source_contact->post_content], ['ID' => 36]);
clean_post_cache(36);

update_post_meta(36, '_et_pb_use_builder', 'on');
update_post_meta(36, '_et_pb_old_content', '');
update_post_meta(36, '_et_pb_built_for_post_type', 'page');

echo "Updated Page 36 (Contact) with native Divi 5 content from Page 691.\n";

// 3. Flush Caches
if (class_exists('\Hummingbird\WP_Hummingbird')) {
    \Hummingbird\WP_Hummingbird::flush_cache(true, true);
    echo "Hummingbird cache flushed.\n";
}
wp_cache_flush();
echo "WordPress object cache flushed.\n";

echo "=== SUCCESS: People and Contact are now 100% Native Divi 5 Live! ===\n";
