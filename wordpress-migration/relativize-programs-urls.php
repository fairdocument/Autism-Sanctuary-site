<?php
// One-shot: make Programs image URLs root-relative.
$id = 27;
$c = get_post_field('post_content', $id);
$n = 0;
$c2 = preg_replace_callback(
	'#https?://[^"/]+(/wp-content/uploads/[^"]+)#',
	function ($m) use (&$n) {
		$n++;
		return $m[1];
	},
	$c
);
if ($n) {
	wp_update_post(['ID' => $id, 'post_content' => wp_slash($c2)]);
}
echo "relativized {$n} urls\n";
echo file_exists(ABSPATH . 'wordpress-migration/custom.css') ? "css ok\n" : "css missing\n";
echo (strpos(file_get_contents(ABSPATH . 'wordpress-migration/custom.css'), 'as-programs-service-photo') !== false)
	? "programs css present\n"
	: "programs css missing\n";
