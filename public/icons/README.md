# Generación de Iconos PWA

Esta carpeta contiene las herramientas para generar los iconos de la aplicación PWA.

## 📋 Iconos Necesarios

- `icon-72x72.png`
- `icon-96x96.png`
- `icon-128x128.png`
- `icon-144x144.png`
- `icon-152x152.png`
- `icon-192x192.png` ⭐ (Principal para Android)
- `icon-384x384.png`
- `icon-512x512.png` ⭐ (Principal para PWA)
- `favicon.png` (48x48 o 32x32)

## 🚀 Métodos de Generación

### Opción 1: Generador HTML (Más fácil)

1. Abre `generator.html` en tu navegador
2. Los iconos se generarán automáticamente
3. Haz clic en "📥 Descargar Todos"
4. Guarda los archivos en esta carpeta

### Opción 2: Python (Requiere librerías)

```bash
pip install cairosvg pillow
python generate_icons.py
```

### Opción 3: ImageMagick (Linux/Mac)

```bash
bash generate_icons.sh
```

### Opción 4: Herramientas Online

1. Ve a: https://www.pwabuilder.com/imageGenerator
2. Sube `icon.svg`
3. Descarga el paquete de iconos
4. Extrae y renombra según los nombres arriba

### Opción 5: Inkscape o GIMP (Manual)

1. Abre `icon.svg` en Inkscape o GIMP
2. Exporta cada tamaño según la lista
3. Guarda con los nombres exactos

## 🎨 Personalización

Edita `icon.svg` con cualquier editor SVG para cambiar el diseño del icono.

## ✅ Verificación

Después de generar los iconos, verifica que tengas todos los archivos:

```bash
ls -la *.png
```

Deberías ver 9 archivos PNG (8 iconos + 1 favicon).
