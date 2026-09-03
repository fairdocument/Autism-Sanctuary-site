<?php
/**
 * Apply published-page copy fixes:
 * - Activity Bar → Activity Barn
 * - ohis → on this
 * - transportatio → transportation
 * - looking-ahead list label colons
 * - strip accidental JSON fragment from Programs text
 */
if (!defined('ABSPATH')) {
	exit(1);
}

$targets = [
	34  => 'donate',
	37  => 'privacy',
	38  => 'terms',
	31  => 'resources',
	409 => 'home',
	27  => 'programs',
];

$backup_dir = WP_CONTENT_DIR . '/as-copy-fix-backup-' . gmdate('Ymd-His');
wp_mkdir_p($backup_dir);

function as_copy_add_looking_ahead_colons($content) {
	$labels = [
		'Activity Barn',
		'Facility upgrades',
		'Workplace opportunities under development',
	];
	$notes = [];
	foreach ($labels as $label) {
		$esc_from = '\\u003cstrong\\u003e' . $label . '\\u003c/strong\\u003e';
		$esc_to = '\\u003cstrong\\u003e' . $label . ':\\u003c/strong\\u003e';
		if (strpos($content, $esc_from) !== false && strpos($content, $esc_to) === false) {
			$content = str_replace($esc_from, $esc_to, $content);
			$notes[] = "colon:$label";
		}
		$html_from = '<strong>' . $label . '</strong>';
		$html_to = '<strong>' . $label . ':</strong>';
		if (strpos($content, $html_from) !== false && strpos($content, $html_to) === false) {
			$content = str_replace($html_from, $html_to, $content);
			$notes[] = "colon-html:$label";
		}
	}
	return [$content, $notes];
}

foreach ($targets as $id => $slug) {
	$post = get_post($id);
	if (!$post) {
		echo "MISS $id $slug\n";
		continue;
	}
	file_put_contents("$backup_dir/{$id}-{$slug}.html", $post->post_content);
	$content = $post->post_content;
	$notes = [];

	// Use word-safe Activity Bar replace (do not match inside Activity Barn).
	$content2 = preg_replace('/\bActivity Bar\b/', 'Activity Barn', $content, -1, $c);
	if ($c > 0) {
		$content = $content2;
		$notes[] = "Activity Bar→Barn x$c";
	}

	$map = [
		'stored ohis site' => 'stored on this site',
		'Content ohis website' => 'Content on this website',
		'Accessibility & transportatio' => 'Accessibility & transportation',
		'Accessibility &amp; transportatio' => 'Accessibility &amp; transportation',
	];
	foreach ($map as $from => $to) {
		if (strpos($content, $from) !== false) {
			$content = str_replace($from, $to, $content);
			$notes[] = "$from→$to";
		}
	}

	[$content, $colon_notes] = as_copy_add_looking_ahead_colons($content);
	$notes = array_merge($notes, $colon_notes);

	if ($slug === 'programs') {
		$content2 = preg_replace('/\},"phone":\{"value":"/', '', $content, -1, $c);
		if ($c > 0) {
			$content = $content2;
			$notes[] = "removed JSON leak x$c";
		}
	}

	if ($content === $post->post_content) {
		echo "SKIP $id /$slug/\n";
		continue;
	}

	wp_update_post([
		'ID'           => $id,
		'post_content' => wp_slash($content),
	]);
	echo "OK $id /$slug/ :: " . implode('; ', $notes) . "\n";
}

echo "Backup: $backup_dir\nDONE\n";
