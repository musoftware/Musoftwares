<?php

$width = 1200;
$height = 630;
$im = imagecreatetruecolor($width, $height);

// Dark gradient background
for ($y = 0; $y < $height; $y++) {
    $r = (int)(11 + ($y / $height) * 15);
    $g = (int)(12 + ($y / $height) * 20);
    $b = (int)(16 + ($y / $height) * 35);
    $color = imagecolorallocate($im, $r, $g, $b);
    imageline($im, 0, $y, $width, $y, $color);
}

// Accent glow circles / tech aesthetic
$glow = imagecolorallocatealpha($im, 102, 252, 241, 110);
imagefilledellipse($im, 200, 150, 450, 450, $glow);
$glow2 = imagecolorallocatealpha($im, 79, 70, 229, 115);
imagefilledellipse($im, 1000, 480, 550, 550, $glow2);

// Overlay logo if exists
$logoPath = dirname(__DIR__) . '/public/favicon.png';
if (file_exists($logoPath)) {
    $logo = imagecreatefrompng($logoPath);
    if ($logo) {
        imagecopyresampled($im, $logo, 100, 180, 0, 0, 140, 140, imagesx($logo), imagesy($logo));
        imagedestroy($logo);
    }
}

// Crisp typography text
$textColor = imagecolorallocate($im, 255, 255, 255);
$subColor = imagecolorallocate($im, 165, 180, 252);
$accentColor = imagecolorallocate($im, 102, 252, 241);

imagestring($im, 5, 270, 210, 'M U S O F T W A R E', $accentColor);
imagestring($im, 5, 270, 250, 'Enterprise Systems & Digital Solutions', $textColor);
imagestring($im, 4, 270, 290, 'ERP, CRM, Cloud Infrastructure and Business Automation', $subColor);

// Bottom border accent
$borderCol = imagecolorallocate($im, 99, 102, 241);
imagefilledrectangle($im, 0, $height - 8, $width, $height, $borderCol);

$target = dirname(__DIR__) . '/public/images/default-meta.png';
imagepng($im, $target);
imagedestroy($im);
echo "Successfully created {$target}\n";
