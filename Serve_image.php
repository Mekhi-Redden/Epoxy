<?php
// ============================================================
// serve_image.php – serves artwork images with a watermark
// Usage: <img src="serve_image.php?file=art_xyz.jpg">
//
// Adds a diagonal semi-transparent "© EPOXY" text watermark
// so screenshots can't be used as clean copies.
// ============================================================

// Only allow image files from the /images/ directory
$file = basename($_GET['file'] ?? '');
$path = __DIR__ . '/images/' . $file;

// Reject if file doesn't exist or isn't an image
if (!$file || !file_exists($path)) {
    http_response_code(404);
    exit("Image not found.");
}

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
if (!in_array($ext, $allowed)) {
    http_response_code(403);
    exit("Forbidden.");
}

// ── Load image into GD ───────────────────────────────────────
$img = match($ext) {
    'jpg', 'jpeg' => imagecreatefromjpeg($path),
    'png'         => imagecreatefrompng($path),
    'webp'        => imagecreatefromwebp($path),
    'gif'         => imagecreatefromgif($path),
    default       => null,
};

if (!$img) {
    http_response_code(500);
    exit("Could not process image.");
}

$w = imagesx($img);
$h = imagesy($img);

// ── Draw watermark ───────────────────────────────────────────
// Semi-transparent white text repeated diagonally across the image

$fontSize  = max(14, (int)($w / 25));    // Scale font with image width
$textColor = imagecolorallocatealpha($img, 255, 255, 255, 80); // 80 = ~37% opacity
$shadowCol = imagecolorallocatealpha($img, 0,   0,   0,   90); // Subtle dark shadow
$label     = "© EPOXY";

// Try to use a TrueType font if available, else fall back to built-in
$fontPath = __DIR__ . '/assets/fonts/DejaVuSans.ttf';
$useTTF   = function_exists('imagettftext') && file_exists($fontPath);

// Tile watermark across the image
$stepX = (int)($w / 3);
$stepY = (int)($h / 3);
$angle = -30;

for ($x = -$stepX; $x < $w + $stepX; $x += $stepX) {
    for ($y = 0; $y < $h + $stepY; $y += $stepY) {
        if ($useTTF) {
            // Shadow
            imagettftext($img, $fontSize, $angle, $x + 2, $y + 2, $shadowCol, $fontPath, $label);
            // Text
            imagettftext($img, $fontSize, $angle, $x,     $y,     $textColor, $fontPath, $label);
        } else {
            // Built-in font fallback (no rotation)
            imagestring($img, 4, $x, $y, $label, $textColor);
        }
    }
}

// ── Output ───────────────────────────────────────────────────
// Cache for 1 hour; vary by file so each image is cached separately
header("Cache-Control: public, max-age=3600");
header("Vary: Accept");

switch ($ext) {
    case 'png':
        header("Content-Type: image/png");
        imagepng($img);
        break;
    case 'webp':
        header("Content-Type: image/webp");
        imagewebp($img);
        break;
    case 'gif':
        header("Content-Type: image/gif");
        imagegif($img);
        break;
    default:
        header("Content-Type: image/jpeg");
        imagejpeg($img, null, 88); // quality 88
}

imagedestroy($img);