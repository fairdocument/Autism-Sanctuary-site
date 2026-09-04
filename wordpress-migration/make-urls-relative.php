<?php
/**
 * Convert staging absolute URLs to root-relative paths in content.
 * Leaves siteurl/home absolute (WP requires that) for the current staging domain.
 *
 * https://staging.example/path → /path
 */
if (!defined('ABSPATH')) {
	exit(1);
}

$host = 'autismsanctuary2-nimbusserver.tempurl.host';
$variants = [
	'https://' . $host,
	'http://' . $host,
	'https:\\/\\/' . $host,
	'http:\\/\\/' . $host,
];

$siteurl = get_option('siteurl');
$home = get_option('home');

$backup = WP_CONTENT_DIR . '/as-url-relative-backup-' . gmdate('Ymd-His') . '.json';
file_put_contents($backup, wp_json_encode([
	'siteurl' => $siteurl,
	'home'    => $home,
	'host'    => $host,
], JSON_PRETTY_PRINT));

function as_rel_replace_in_string($content, $variants, &$count) {
	foreach ($variants as $from) {
		$n = 0;
		$content = str_replace($from, '', $content, $n);
		$count += $n;
	}
	return $content;
}

global $wpdb;
$total = 0;

$post_ids = get_posts([
	'post_type'      => ['page', 'post', 'nav_menu_item'],
	'post_status'    => 'any',
	'posts_per_page' => -1,
	'fields'         => 'ids',
]);

$changed_posts = 0;
foreach ($post_ids as $id) {
	$post = get_post($id);
	if (!$post) {
		continue;
	}
	$count = 0;
	$new = as_rel_replace_in_string($post->post_content, $variants, $count);
	if ($count > 0) {
		wp_update_post([
			'ID'           => $id,
			'post_content' => wp_slash($new),
		]);
		$changed_posts++;
		$total += $count;
		echo "post #{$id} {$post->post_name}: {$count}\n";
	}
}

$meta_rows = $wpdb->get_results($wpdb->prepare(
	"SELECT meta_id, post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE meta_value LIKE %s LIMIT 5000",
	'%' . $wpdb->esc_like($host) . '%'
));
$changed_meta = 0;
foreach ($meta_rows as $row) {
	if (strpos((string) $row->meta_key, '_wp_attached_file') !== false) {
		continue;
	}
	$count = 0;
	$new = as_rel_replace_in_string($row->meta_value, $variants, $count);
	if ($count > 0) {
		update_metadata_by_mid('post', $row->meta_id, $new);
		$changed_meta++;
		$total += $count;
		echo "meta #{$row->meta_id} post {$row->post_id} {$row->meta_key}: {$count}\n";
	}
}

$redirects = get_option('as_path_redirects');
if (is_array($redirects)) {
	$rd_count = 0;
	foreach ($redirects as $k => $v) {
		if (!is_string($v)) {
			continue;
		}
		$c = 0;
		$redirects[$k] = as_rel_replace_in_string($v, $variants, $c);
		$rd_count += $c;
	}
	if ($rd_count > 0) {
		update_option('as_path_redirects', $redirects);
		$total += $rd_count;
		echo "as_path_redirects: {$rd_count}\n";
		foreach ($redirects as $k => $v) {
			echo "  {$k} => {$v}\n";
		}
	}
}

update_option('siteurl', $siteurl);
update_option('home', $home);

echo "Changed posts: {$changed_posts}, meta rows: {$changed_meta}, total replacements: {$total}\n";
echo 'siteurl=' . get_option('siteurl') . "\n";
echo 'home=' . get_option('home') . "\n";
echo "Backup: {$backup}\n";
echo "DONE\n";
