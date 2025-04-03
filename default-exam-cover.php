<?php
// Set the content type to image/jpeg
header('Content-Type: image/jpeg');

// Create a blank image (600x200 pixels)
$image = imagecreatetruecolor(600, 200);

// Define colors
$background = imagecolorallocate($image, 142, 104, 204); // Purple background (matches your theme)
$text_color = imagecolorallocate($image, 255, 255, 255); // White text

// Fill the background
imagefill($image, 0, 0, $background);

// Add some design elements (diagonal lines)
$light_purple = imagecolorallocate($image, 162, 124, 224);
for ($i = 0; $i < 600; $i += 20) {
    imageline($image, $i, 0, $i - 200, 200, $light_purple);
}

// Add text
$font = 5; // Built-in font
$text = "ExamMaker";
$text_width = imagefontwidth($font) * strlen($text);
$text_height = imagefontheight($font);
$x = (600 - $text_width) / 2;
$y = (200 - $text_height) / 2;

// Draw the text
imagestring($image, $font, $x, $y, $text, $text_color);

// Output the image
imagejpeg($image, null, 90);

// Free memory
imagedestroy($image);
?>
