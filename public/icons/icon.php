<?php
// Suprimir warnings y notices
error_reporting(0);
ini_set('display_errors', 0);

// Generar icono PNG dinámicamente
$size = isset($_GET['size']) ? (int)$_GET['size'] : 192;
$size = max(16, min(512, $size)); // Limitar entre 16 y 512

// Crear imagen
$img = imagecreatetruecolor($size, $size);

// Colores
$bg = imagecolorallocate($img, 59, 130, 246); // #3b82f6
$white = imagecolorallocate($img, 255, 255, 255);
$green = imagecolorallocate($img, 16, 185, 129); // #10b981

// Fondo
imagefilledrectangle($img, 0, 0, $size, $size, $bg);

// Dibujar tablero simplificado (3 rectángulos verticales)
$colWidth = (int)($size * 0.2);
$colHeight = (int)($size * 0.5);
$startY = (int)($size * 0.25);
$gap = (int)($size * 0.05);

// Columna 1
$x1 = (int)($size * 0.15);
imagefilledrectangle($img, $x1, $startY, $x1 + $colWidth, $startY + $colHeight, $white);

// Columna 2
$x2 = $x1 + $colWidth + $gap;
imagefilledrectangle($img, $x2, $startY, $x2 + $colWidth, $startY + $colHeight, $white);

// Columna 3
$x3 = $x2 + $colWidth + $gap;
imagefilledrectangle($img, $x3, $startY, $x3 + $colWidth, $startY + $colHeight, $white);

// Checkmark (círculo verde)
$checkX = (int)($size * 0.75);
$checkY = (int)($size * 0.25);
$checkR = (int)($size * 0.12);
imagefilledellipse($img, $checkX, $checkY, $checkR * 2, $checkR * 2, $green);

// Checkmark símbolo (línea blanca)
imagesetthickness($img, max(2, (int)($size * 0.02)));
imageline($img, $checkX - $checkR/2, $checkY, $checkX - $checkR/4, $checkY + $checkR/2, $white);
imageline($img, $checkX - $checkR/4, $checkY + $checkR/2, $checkX + $checkR/2, $checkY - $checkR/2, $white);

// Headers
header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400'); // Cache 1 día

// Output
imagepng($img);
imagedestroy($img);
