# 📋 Planificador Kanban - Sistema de Gestión de Proyectos

Sistema completo de gestión de tareas basado en metodología Kanban con soporte para Sprints, tableros múltiples, bitácora de actividades y gestión de archivos adjuntos.

## 🚀 Características Principales

- ✅ **Tableros Múltiples**: Gestiona diferentes proyectos o clientes
- 🏃 **Sistema de Sprints**: Planificación ágil con seguimiento de progreso
- 📊 **Vista Kanban y Lista**: Dos formas de visualizar tus tareas
- 📝 **Bitácora de Actividades**: Documenta procesos con texto y archivos adjuntos
- 📎 **Gestión de Archivos**: Sube imágenes, documentos, PDFs, etc.
- ⚡ **Story Points**: Estimación de complejidad y esfuerzo
- 📅 **Gestión de Fechas**: Proyectos de corto y largo plazo
- 🎯 **Categorización**: Organiza por tipo de trabajo (Soporte/Desarrollo)
- 🔒 **Seguridad**: Protección CSRF, validación de archivos, sanitización XSS

## 📁 Estructura del Proyecto (MVC)

```
planificador/
├── app/
│   ├── controllers/          # Controladores de la aplicación
│   │   ├── BaseController.php      # Controlador base con utilidades
│   │   ├── BoardController.php     # Gestión de tableros
│   │   ├── SprintController.php    # Gestión de sprints
│   │   ├── CardController.php      # Gestión de tarjetas
│   │   └── ActivityController.php  # Gestión de actividades
│   ├── models/              # Modelos de datos
│   │   ├── Database.php           # Singleton de conexión PDO
│   │   ├── Board.php              # Modelo de tableros
│   │   ├── Sprint.php             # Modelo de sprints
│   │   ├── CardList.php           # Modelo de listas/columnas
│   │   ├── Card.php               # Modelo de tarjetas
│   │   └── Activity.php           # Modelo de actividades
│   └── views/               # Vistas de la aplicación
│       ├── layouts/
│       │   └── main.php           # Layout principal HTML
│       ├── components/
│       │   ├── header.php         # Navbar y controles
│       │   └── modals.php         # Todos los modales
│       └── home.php               # Vista Kanban y Lista
├── config/                  # Archivos de configuración
│   ├── app.php                    # Configuración general
│   ├── database.php               # Configuración de BD
│   └── autoload.php               # PSR-4 Autoloader
├── public/                  # Archivos públicos accesibles
│   ├── css/
│   │   └── styles.css             # Estilos CSS (~700 líneas)
│   ├── js/
│   │   └── app.js                 # JavaScript (~900 líneas)
│   └── uploads/                   # Archivos subidos por usuarios
│       ├── .htaccess              # Protección de ejecución PHP
│       └── index.php              # Bloqueo de listado directo
├── index.php                # Punto de entrada principal
├── .htaccess                # Configuración Apache
├── setup_database.sql       # Script de creación de BD MySQL
└── README.md                # Esta documentación
```

## 🗄️ Base de Datos

### Motor Soportado
- **MySQL 5.7+** (recomendado para producción)
- **SQLite 3** (alternativa para desarrollo)

### Tablas

1. **boards** - Tableros de proyecto
2. **sprints** - Ciclos de trabajo ágiles
3. **lists** - Columnas del tablero
4. **cards** - Tareas individuales
5. **card_activities** - Bitácora de actividades

### Índices de Rendimiento
- 13 índices estratégicos para optimizar consultas frecuentes
- Claves foráneas en `board_id`, `sprint_id`, `list_id`, `card_id`

## ⚙️ Instalación

### Requisitos
- PHP 7.4 o superior
- MySQL 5.7+ / MariaDB 10.3+
- Apache con mod_rewrite habilitado
- Extensiones PHP: PDO, pdo_mysql

### Pasos

1. **Clonar el proyecto**
```bash
git clone https://github.com/watydiaz/planner.git
cd planificador
```

2. **Crear la base de datos**
```bash
mysql -u usuario -p < setup_database.sql
```

3. **Configurar la conexión**
Editar `config/database.php` con tus credenciales

4. **Configurar URLs**
Editar `config/app.php` con tu dominio/ruta

5. **Configurar permisos**
```bash
chmod -R 755 public/uploads
```

6. **Acceder**
```
http://localhost/planificador
```

## 🔐 Seguridad

- ✅ Protección CSRF
- ✅ Prepared Statements (SQL Injection)
- ✅ Sanitización HTML (XSS)
- ✅ Validación de archivos
- ✅ Headers de seguridad
- ✅ Protección de directorios

## 📚 Arquitectura MVC

### Modelos
- Database, Board, Sprint, CardList, Card, Activity

### Controladores
- Base, Board, Sprint, Card, Activity

### Vistas
- Layout principal, componentes (header, modals), home

## 🎨 Tecnologías

- **Backend**: PHP 7.4+, PDO
- **Frontend**: Bootstrap 5.3.3, Vanilla JS
- **Base de Datos**: MySQL / SQLite
- **Tipografía**: Google Fonts Inter

## 📊 Uso

1. **Crear Tablero**: Click en "Nuevo Tablero"
2. **Gestionar Tareas**: Arrastrar y soltar entre columnas
3. **Agregar Actividades**: Documentar procesos en bitácora
4. **Crear Sprints**: Planificar ciclos de trabajo

## 🛠️ Mantenimiento

### Backup
```bash
mysqldump -u usuario -p planificador_kanban > backup.sql
```

### Limpieza
```bash
find public/uploads -type f -mtime +30 -delete
```

## 📝 Changelog

### v2.0 (Nov 2025)
- Refactoring completo a MVC
- 11 archivos de modelos/controladores
- CSS y JS externalizados
- Autoloader PSR-4
- Documentación completa

## 👨‍💻 Autor

**Karol Diaz**
- GitHub: [@watydiaz](https://github.com/watydiaz)

---

**Desarrollado con ❤️ para gestión eficiente de proyectos**
