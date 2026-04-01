<?php
/**
 * Create Default Profile Image
 * Run this script once to generate a default.png image
 */

// Create a 200x200 image
$width = 200;
$height = 200;
$image = imagecreatetruecolor($width, $height);

// Set background color (light gray)
$bgColor = imagecolorallocate($image, 230, 230, 230);
imagefill($image, 0, 0, $bgColor);

// Draw a circle for head (dark gray)
$circleColor = imagecolorallocate($image, 150, 150, 150);
imagefilledellipse($image, 100, 70, 60, 60, $circleColor);

// Draw a larger ellipse for body
imagefilledellipse($image, 100, 160, 100, 90, $circleColor);

// Add text
$textColor = imagecolorallocate($image, 100, 100, 100);
$font = 3; // Built-in font
$text = "No Photo";
$textWidth = imagefontwidth($font) * strlen($text);
$x = ($width - $textWidth) / 2;
$y = $height - 20;
imagestring($image, $font, $x, $y, $text, $textColor);

// Save the image
$filename = __DIR__ . '/default.png';
if (imagepng($image, $filename)) {
    echo "✓ Default image created successfully: $filename\n";
    echo "File size: " . filesize($filename) . " bytes\n";
    echo "Dimensions: {$width}x{$height}px\n";
} else {
    echo "✗ Failed to create default image\n";
}

// Free up memory
imagedestroy($image);

// Also create a .htaccess for security
$htaccess = __DIR__ . '/.htaccess';
$htaccessContent = <<<HTACCESS
# Prevent PHP execution in image directory
<FilesMatch "\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)$">
    Require all denied
</FilesMatch>

# Allow only image files
<FilesMatch "\.(jpg|jpeg|png|gif|webp|svg)$">
    Require all granted
</FilesMatch>

# Prevent directory listing
Options -Indexes

# Set proper MIME types
<IfModule mod_mime.c>
    AddType image/jpeg .jpg .jpeg
    AddType image/png .png
    AddType image/gif .gif
    AddType image/webp .webp
</IfModule>
HTACCESS;

if (file_put_contents($htaccess, $htaccessContent)) {
    echo "✓ Security .htaccess created successfully\n";
} else {
    echo "✗ Failed to create .htaccess (may not be needed on your server)\n";
}

echo "\nSetup complete! You can now delete this script.\n";
?>

