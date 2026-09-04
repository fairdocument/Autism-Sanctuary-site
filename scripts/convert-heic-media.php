<?php
/**
 * Convert WordPress HEIC attachments to JPEG and regenerate thumbnails.
 *
 * Usage (on the WP host):
 *   wp eval-file scripts/convert-heic-media.php --path=/path/to/wordpress
 *
 * Requires Imagick with HEIC support, or pre-converted sibling .jpg files
 * next to each .HEIC original (e.g. produced locally with `sips`).
 */

if (!defined('ABSPATH')) {
	exit(1);
}

require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

global $wpdb;

$uploads = wp_upload_dir();
$basedir = $uploads['basedir'];

function heic_log(string $msg): void {
	fwrite(STDOUT, $msg . PHP_EOL);
}

function find_heic_source(string $basedir, string $attached_rel): ?string {
	$dir = dirname($attached_rel);
	$base = pathinfo($attached_rel, PATHINFO_FILENAME);
	$base = preg_replace('/-scaled$/', '', $base);
	$base = preg_replace('/-\d+x\d+$/', '', $base);

	foreach ([
		"{$dir}/{$base}.HEIC",
		"{$dir}/{$base}.heic",
		"{$dir}/{$base}.bak.HEIC",
		"{$dir}/{$base}.bak.heic",
		$attached_rel,
	] as $rel) {
		$path = $basedir . '/' . ltrim($rel, '/');
		if (is_file($path) && preg_match('/\.heic$/i', $path)) {
			return $path;
		}
	}
	return null;
}

function convert_heic_to_jpeg(string $heic_path, string $jpeg_path): bool {
	$img = new Imagick($heic_path);
	$img->setImageFormat('jpeg');
	$img->setImageCompressionQuality(88);
	$img->stripImage();
	$ok = $img->writeImage($jpeg_path);
	$img->clear();
	$img->destroy();
	return $ok && is_file($jpeg_path);
}

function delete_heic_variants(string $basedir, string $attached_rel): int {
	$dir = dirname($basedir . '/' . ltrim($attached_rel, '/'));
	$base = pathinfo($attached_rel, PATHINFO_FILENAME);
	$base = preg_replace('/-scaled$/', '', $base);
	$base = preg_replace('/-rotated$/', '', $base);
	$deleted = 0;
	foreach (array_merge(
		glob($dir . '/' . $base . '-*.heic') ?: [],
		glob($dir . '/' . $base . '-*.HEIC') ?: []
	) as $f) {
		if (@unlink($f)) {
			$deleted++;
		}
	}
	return $deleted;
}

$ids = $wpdb->get_col("
	SELECT ID FROM {$wpdb->posts}
	WHERE post_type = 'attachment'
	  AND (
		LOWER(guid) LIKE '%.heic'
		OR post_mime_type LIKE '%heic%'
		OR post_mime_type LIKE '%heif%'
	  )
	ORDER BY ID ASC
");
$meta_ids = $wpdb->get_col("
	SELECT post_id FROM {$wpdb->postmeta}
	WHERE meta_key = '_wp_attachment_metadata'
	  AND meta_value LIKE '%.heic%'
");
$ids = array_values(array_unique(array_map('intval', array_merge($ids ?: [], $meta_ids ?: []))));
heic_log('Found ' . count($ids) . ' attachments to process');

$ok = 0;
$skip = 0;
$fail = 0;

foreach ($ids as $id) {
	$attached = get_post_meta($id, '_wp_attached_file', true);
	if (!$attached) {
		heic_log("[SKIP] #$id no _wp_attached_file");
		$skip++;
		continue;
	}

	$dir = dirname($attached);
	$base = pathinfo($attached, PATHINFO_FILENAME);
	$base = preg_replace('/-scaled$/', '', $base);
	$base = preg_replace('/-rotated$/', '', $base);

	$jpeg_rel = null;
	foreach ([
		"$dir/$base.jpg",
		"$dir/$base-scaled.jpg",
		"$dir/$base-rotated.jpg",
		preg_match('/\.jpe?g$/i', $attached) ? $attached : null,
	] as $rel) {
		if ($rel && is_file($basedir . '/' . ltrim($rel, '/'))) {
			$jpeg_rel = $rel;
			break;
		}
	}

	try {
		if (!$jpeg_rel) {
			$heic_src = find_heic_source($basedir, $attached);
			if (!$heic_src) {
				heic_log("[FAIL] #$id no HEIC/JPEG source for $attached");
				$fail++;
				continue;
			}
			$jpeg_abs = preg_replace('/(\.bak)?\.heic$/i', '.jpg', $heic_src);
			$jpeg_abs = preg_replace('/\.bak\.jpg$/i', '.jpg', $jpeg_abs);
			heic_log('[CONV] #' . $id . ' ' . basename($heic_src) . ' -> ' . basename($jpeg_abs));
			if (!is_file($jpeg_abs) && !convert_heic_to_jpeg($heic_src, $jpeg_abs)) {
				heic_log("[FAIL] #$id convert failed (pre-convert with sips if Imagick lacks HEIC)");
				$fail++;
				continue;
			}
			$jpeg_rel = ltrim(str_replace($basedir, '', $jpeg_abs), '/');
		} else {
			heic_log("[FIX] #$id -> $jpeg_rel");
		}

		$jpeg_abs = $basedir . '/' . ltrim($jpeg_rel, '/');
		$deleted = delete_heic_variants($basedir, $attached) + delete_heic_variants($basedir, $jpeg_rel);
		if ($deleted) {
			heic_log("  deleted $deleted HEIC variants");
		}

		update_post_meta($id, '_wp_attached_file', $jpeg_rel);
		$wpdb->update(
			$wpdb->posts,
			[
				'post_mime_type' => 'image/jpeg',
				'guid' => $uploads['baseurl'] . '/' . ltrim($jpeg_rel, '/'),
			],
			['ID' => $id]
		);

		$new_meta = wp_generate_attachment_metadata($id, $jpeg_abs);
		if (empty($new_meta) || is_wp_error($new_meta)) {
			heic_log('[FAIL] #' . $id . ' metadata: ' . (is_wp_error($new_meta) ? $new_meta->get_error_message() : 'empty'));
			$fail++;
			continue;
		}
		wp_update_attachment_metadata($id, $new_meta);
		$thumb = $new_meta['sizes']['thumbnail']['file'] ?? '(none)';
		heic_log("[OK] #$id thumb=$thumb");
		$ok++;
	} catch (Throwable $e) {
		heic_log('[FAIL] #' . $id . ' ' . $e->getMessage());
		$fail++;
	}
}

heic_log("Done. ok=$ok skip=$skip fail=$fail");
