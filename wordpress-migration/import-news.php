<?php
/**
 * Import news JSON exported from live Autism Sanctuary WP.
 * Run: wp eval-file wordpress-migration/import-news.php
 */

if (!defined('ABSPATH')) {
	exit(1);
}

$dir = __DIR__ . '/news-export';
$files = glob($dir . '/post-*.json');
if (!$files) {
	echo "No news export files found\n";
	return;
}

$count = 0;
foreach ($files as $file) {
	$raw = file_get_contents($file);
	// Strip PHP notices that may precede JSON
	$raw = preg_replace('/^.*?(?=\{)/s', '', $raw);
	$data = json_decode($raw, true);
	if (!$data || empty($data['post_title'])) {
		echo "Skip bad file: {$file}\n";
		continue;
	}

	$slug = $data['post_name'];
	$existing_id = 0;
	$q = new WP_Query([
		'name'           => $slug,
		'post_type'      => 'post',
		'post_status'    => 'any',
		'posts_per_page' => 1,
	]);
	if ($q->have_posts()) {
		$existing_id = (int) $q->posts[0]->ID;
	}

	$content = $data['post_content'] ?? '';
	// Point relative uploads at live site until media is migrated
	$content = preg_replace(
		'#https?://(www\.)?autismsanctuary\.org/#',
		'https://www.autismsanctuary.org/',
		$content
	);

	$args = [
		'post_title'   => $data['post_title'],
		'post_name'    => $slug,
		'post_content' => $content,
		'post_excerpt' => $data['post_excerpt'] ?? '',
		'post_status'  => 'publish',
		'post_type'    => 'post',
		'post_date'    => $data['post_date'] ?? current_time('mysql'),
		'post_author'  => 1,
	];

	if ($existing_id) {
		$args['ID'] = $existing_id;
		$id = wp_update_post($args, true);
	} else {
		$id = wp_insert_post($args, true);
	}

	if (is_wp_error($id)) {
		echo 'ERROR ' . $slug . ': ' . $id->get_error_message() . "\n";
		continue;
	}

	echo "Post OK: {$slug} (#{$id})\n";
	$count++;
}

echo "Imported {$count} posts\n";
