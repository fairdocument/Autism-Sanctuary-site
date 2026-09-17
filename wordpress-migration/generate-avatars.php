<?php
/**
 * Generate and sideload clean initials avatars for the Board and Management.
 * Run: wp eval-file wordpress-migration/generate-avatars.php
 */

if (!defined('ABSPATH')) {
    exit(1);
}

$people = [
    ['initials' => 'JB', 'name' => 'Jason Brewster', 'slug' => 'avatar-jason-brewster'],
    ['initials' => 'RK', 'name' => 'Robert Kreps', 'slug' => 'avatar-robert-kreps'],
    ['initials' => 'MO', 'name' => 'Matthew Osborne', 'slug' => 'avatar-matthew-osborne'],
    ['initials' => 'RN', 'name' => 'Rose Neville', 'slug' => 'avatar-rose-neville'],
    ['initials' => 'OB', 'name' => 'Olivia Bruno', 'slug' => 'avatar-olivia-bruno'],
    ['initials' => 'IK', 'name' => 'Isabelle Kueser', 'slug' => 'avatar-isabelle-kueser'],
];

$upload_dir = wp_upload_dir();
$avatar_dir = $upload_dir['basedir'] . '/avatars';
wp_mkdir_p($avatar_dir);

$avatar_urls = [];

foreach ($people as $p) {
    // Check if attachment already exists
    $existing = get_posts([
        'post_type'   => 'attachment',
        'name'        => $p['slug'],
        'post_status' => 'inherit',
        'numberposts' => 1,
    ]);

    if (!empty($existing)) {
        $avatar_urls[$p['name']] = wp_get_attachment_url($existing[0]->ID);
        echo "Found existing avatar for {$p['name']}: {$avatar_urls[$p['name']]}\n";
        continue;
    }

    // Generate 300x300 PNG with GD
    $img = imagecreatetruecolor(300, 300);
    
    // Background gradient/tint: soft warm sage
    $bg = imagecolorallocate($img, 237, 241, 238); // #EDF1EE
    imagefill($img, 0, 0, $bg);

    // Subtle border
    $border = imagecolorallocate($img, 218, 226, 220);
    imagerectangle($img, 0, 0, 299, 299, $border);

    // Text color: Forest deep #1E3D2C
    $textColor = imagecolorallocate($img, 30, 61, 44);

    // Try finding a system serif or sans font for TTF, or fallback to imagestring
    $fontFile = null;
    $possibleFonts = [
        '/usr/share/fonts/truetype/dejavu/DejaVuSerif-Bold.ttf',
        '/usr/share/fonts/truetype/liberation/LiberationSerif-Bold.ttf',
        '/System/Library/Fonts/Times.ttc',
        '/System/Library/Fonts/Supplemental/Georgia.ttf',
    ];
    foreach ($possibleFonts as $f) {
        if (file_exists($f)) {
            $fontFile = $f;
            break;
        }
    }

    if ($fontFile && function_exists('imagettftext')) {
        $fontSize = 80;
        $box = imagettfbbox($fontSize, 0, $fontFile, $p['initials']);
        $textWidth = abs($box[4] - $box[0]);
        $textHeight = abs($box[5] - $box[1]);
        $x = (300 - $textWidth) / 2;
        $y = (300 + $textHeight) / 2 - 5;
        imagettftext($img, $fontSize, 0, (int)$x, (int)$y, $textColor, $fontFile, $p['initials']);
    } else {
        // Fallback simple string
        $fontSize = 5;
        $textWidth = imagefontwidth($fontSize) * strlen($p['initials']);
        $textHeight = imagefontheight($fontSize);
        $x = (300 - $textWidth) / 2;
        $y = (300 - $textHeight) / 2;
        imagestring($img, $fontSize, (int)$x, (int)$y, $p['initials'], $textColor);
    }

    $filePath = $avatar_dir . '/' . $p['slug'] . '.png';
    imagepng($img, $filePath, 9);
    imagedestroy($img);

    // Sideload into Media Library
    $filetype = wp_check_filetype(basename($filePath), null);
    $attachment = [
        'guid'           => $upload_dir['baseurl'] . '/avatars/' . basename($filePath),
        'post_mime_type' => $filetype['type'],
        'post_title'     => $p['name'] . ' Avatar Placeholder',
        'post_name'      => $p['slug'],
        'post_content'   => '',
        'post_status'    => 'inherit',
    ];
    $attach_id = wp_insert_attachment($attachment, $filePath);
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $filePath);
    wp_update_attachment_metadata($attach_id, $attach_data);

    $avatar_urls[$p['name']] = wp_get_attachment_url($attach_id);
    echo "Created avatar for {$p['name']}: {$avatar_urls[$p['name']]}\n";
}

echo "=== All avatars ready ===\n";
