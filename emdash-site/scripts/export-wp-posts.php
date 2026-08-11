<?php
/**
 * Export WordPress posts as JSON for EmDash restore.
 * Usage: wp --path=... eval-file export-posts-json.php
 */
$q = new WP_Query([
	'post_type' => 'post',
	'post_status' => ['publish', 'draft'],
	'posts_per_page' => -1,
	'orderby' => 'date',
	'order' => 'DESC',
]);
$out = [];
foreach ($q->posts as $p) {
	$html = apply_filters('the_content', $p->post_content);
	$excerpt = $p->post_excerpt ?: wp_trim_words(wp_strip_all_tags($html), 40, '…');
	$out[] = [
		'id' => $p->ID,
		'slug' => $p->post_name,
		'title' => html_entity_decode(get_the_title($p), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
		'status' => $p->post_status === 'publish' ? 'published' : 'draft',
		'date' => get_post_time('c', true, $p),
		'modified' => get_post_modified_time('c', true, $p),
		'excerpt' => $excerpt,
		'html' => $html,
	];
}
echo wp_json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
