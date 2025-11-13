#!/bin/bash
# Script para generar iconos PNG desde SVG usando ImageMagick
# Uso: bash generate_icons.sh

echo "Generando iconos PNG desde SVG..."

sizes=(72 96 128 144 152 192 384 512)

for size in "${sizes[@]}"; do
    convert icon.svg -resize ${size}x${size} icon-${size}x${size}.png
    echo "✓ Generado: icon-${size}x${size}.png"
done

# Generar favicon.ico
convert icon.svg -resize 48x48 -define icon:auto-resize=16,32,48 favicon.ico
echo "✓ Generado: favicon.ico"

echo ""
echo "✅ Todos los iconos generados correctamente!"
echo ""
echo "Si no tienes ImageMagick instalado:"
echo "  Windows: https://imagemagick.org/script/download.php"
echo "  O usa: https://www.favicon-generator.org/"
