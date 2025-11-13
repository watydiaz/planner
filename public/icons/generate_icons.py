#!/usr/bin/env python3
"""
Script para generar iconos PNG de diferentes tamaños desde SVG
Requiere: pip install cairosvg pillow
"""

try:
    import cairosvg
    from PIL import Image
    import io
    import os

    sizes = [72, 96, 128, 144, 152, 192, 384, 512]
    svg_file = 'icon.svg'
    
    print("Generando iconos PNG...")
    
    for size in sizes:
        output_file = f'icon-{size}x{size}.png'
        
        # Convertir SVG a PNG usando cairosvg
        png_data = cairosvg.svg2png(
            url=svg_file,
            output_width=size,
            output_height=size
        )
        
        # Guardar el archivo
        with open(output_file, 'wb') as f:
            f.write(png_data)
        
        print(f'✓ Generado: {output_file}')
    
    # Generar favicon.ico (16x16, 32x32, 48x48)
    print("\nGenerando favicon.ico...")
    favicon_sizes = [16, 32, 48]
    images = []
    
    for size in favicon_sizes:
        png_data = cairosvg.svg2png(
            url=svg_file,
            output_width=size,
            output_height=size
        )
        img = Image.open(io.BytesIO(png_data))
        images.append(img)
    
    # Guardar como ICO
    images[0].save(
        'favicon.ico',
        format='ICO',
        sizes=[(16, 16), (32, 32), (48, 48)],
        append_images=images[1:]
    )
    print('✓ Generado: favicon.ico')
    
    print("\n✅ Todos los iconos generados correctamente!")
    print("\nSi no tienes las librerías, instálalas con:")
    print("  pip install cairosvg pillow")

except ImportError as e:
    print("⚠️  Librerías no encontradas. Generando iconos manualmente...")
    print("\nPara generar automáticamente, instala:")
    print("  pip install cairosvg pillow")
    print("\nO genera los iconos manualmente:")
    print("1. Abre icon.svg en Inkscape, GIMP o un editor online")
    print("2. Exporta en los siguientes tamaños:")
    
    sizes = [72, 96, 128, 144, 152, 192, 384, 512]
    for size in sizes:
        print(f"   - icon-{size}x{size}.png ({size}x{size} px)")
    
    print("\n3. Genera favicon.ico con tamaños 16x16, 32x32, 48x48")
    print("   Usa: https://www.favicon-generator.org/")
