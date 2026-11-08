<?php

$srcFile = '/Users/mizanurrahman/.gemini/antigravity/brain/c7e4e7dd-252d-446a-bd8b-d4d859ae3c04/.user_uploaded/media_1793991355246_dee5a58d.png';
if (! file_exists($srcFile)) {
    exit("Source file does not exist: $srcFile\n");
}

$src = imagecreatefrompng($srcFile);
$srcW = imagesx($src);
$srcH = imagesy($src);

echo "Loaded uploaded image: {$srcW}x{$srcH}\n";

function makeSquareIcon($src, $srcW, $srcH, $size, $paddingRatio = 0.15, $bgRgb = null, $isRound = false)
{
    $dst = imagecreatetruecolor($size, $size);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);

    if ($bgRgb === null) {
        $trans = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $size, $size, $trans);
    } else {
        if ($isRound) {
            $trans = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, $size, $size, $trans);
            $fill = imagecolorallocate($dst, $bgRgb[0], $bgRgb[1], $bgRgb[2]);
            imagefilledellipse($dst, (int) ($size / 2), (int) ($size / 2), $size, $size, $fill);
        } else {
            // Squircle / rounded rectangle or flat fill
            $fill = imagecolorallocate($dst, $bgRgb[0], $bgRgb[1], $bgRgb[2]);
            imagefilledrectangle($dst, 0, 0, $size, $size, $fill);
        }
    }

    imagealphablending($dst, true);

    $avail = $size * (1 - ($paddingRatio * 2));
    $scale = min($avail / $srcW, $avail / $srcH);
    $newW = (int) round($srcW * $scale);
    $newH = (int) round($srcH * $scale);
    $dstX = (int) round(($size - $newW) / 2);
    $dstY = (int) round(($size - $newH) / 2);

    imagecopyresampled($dst, $src, $dstX, $dstY, 0, 0, $newW, $newH, $srcW, $srcH);

    return $dst;
}

function makeHorizontalLogo($src, $srcW, $srcH, $width, $height, $isDark = false)
{
    $dst = imagecreatetruecolor($width, $height);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $trans = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefilledrectangle($dst, 0, 0, $width, $height, $trans);
    imagealphablending($dst, true);

    // Draw icon on left side
    $iconTargetH = (int) round($height * 0.78);
    $iconScale = $iconTargetH / $srcH;
    $iconTargetW = (int) round($srcW * $iconScale);
    $iconX = (int) round($height * 0.15);
    $iconY = (int) round(($height - $iconTargetH) / 2);

    imagecopyresampled($dst, $src, $iconX, $iconY, 0, 0, $iconTargetW, $iconTargetH, $srcW, $srcH);

    // Draw "Bondoo" text next to icon
    $fontFile = '/System/Library/Fonts/Supplemental/Arial Bold.ttf';
    $fontSize = (int) round($height * 0.44);
    $textX = $iconX + $iconTargetW + (int) round($height * 0.18);
    $textY = (int) round($height * 0.68);

    if ($isDark) {
        $textColor = imagecolorallocate($dst, 255, 255, 255);
    } else {
        $textColor = imagecolorallocate($dst, 24, 32, 51); // Dark navy / slate
    }
    $accentColor = imagecolorallocate($dst, 79, 70, 229); // Vibrant indigo/blue accent

    imagettftext($dst, $fontSize, 0, $textX, $textY, $textColor, $fontFile, 'Bond');
    $box = imagettfbbox($fontSize, 0, $fontFile, 'Bond');
    $bondWidth = abs($box[4] - $box[0]);
    imagettftext($dst, $fontSize, 0, $textX + $bondWidth, $textY, $accentColor, $fontFile, 'oo');

    return $dst;
}

// 1. Generate Web Assets
$publicImages = __DIR__.'/public/images';
if (! is_dir($publicImages)) {
    mkdir($publicImages, 0755, true);
}

// Raw icon & Web favicons
$iconRaw = makeSquareIcon($src, $srcW, $srcH, 512, 0.05, null);
imagepng($iconRaw, "$publicImages/bondhoo-icon.png");
imagepng($iconRaw, "$publicImages/bondhoo-icon-512.png");

$icon192 = makeSquareIcon($src, $srcW, $srcH, 192, 0.08, null);
imagepng($icon192, "$publicImages/bondhoo-icon-192.png");

$favicon = makeSquareIcon($src, $srcW, $srcH, 64, 0.06, null);
imagepng($favicon, __DIR__.'/public/favicon.png');
imagepng($favicon, "$publicImages/bondhoo-favicon.png");

// Also create public/favicon.ico
imagepng($favicon, __DIR__.'/public/favicon.ico');

// Horizontal web logos (Light and Dark)
$webLogo = makeHorizontalLogo($src, $srcW, $srcH, 833, 245, false);
imagepng($webLogo, "$publicImages/bondhoo-logo.png");

$webLogoDark = makeHorizontalLogo($src, $srcW, $srcH, 833, 245, true);
imagepng($webLogoDark, "$publicImages/bondhoo-logo-white.png");

echo "Generated Web assets successfully!\n";

// 2. Generate Android Assets
$androidRes = __DIR__.'/android/app/src/main/res';

// Foreground for adaptive launcher icon (432x432 with icon in center safe zone)
$launcherFg = makeSquareIcon($src, $srcW, $srcH, 432, 0.22, null);
imagepng($launcherFg, "$androidRes/drawable/ic_launcher_foreground.png");

// Drawables for within app
imagepng($iconRaw, "$androidRes/drawable/bondhoo_icon.png");
imagepng($webLogo, "$androidRes/drawable/bondhoo_logo.png");

// Density-specific launcher mipmaps
$densities = [
    'mipmap-mdpi' => 48,
    'mipmap-hdpi' => 72,
    'mipmap-xhdpi' => 96,
    'mipmap-xxhdpi' => 144,
    'mipmap-xxxhdpi' => 192,
];

foreach ($densities as $folder => $size) {
    $dir = "$androidRes/$folder";
    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    // Square launcher icon with crisp white background
    $iconSquare = makeSquareIcon($src, $srcW, $srcH, $size, 0.12, [255, 255, 255], false);
    imagepng($iconSquare, "$dir/ic_launcher.png");

    // Round launcher icon
    $iconRound = makeSquareIcon($src, $srcW, $srcH, $size, 0.12, [255, 255, 255], true);
    imagepng($iconRound, "$dir/ic_launcher_round.png");
}

echo "Generated Android assets successfully!\n";
