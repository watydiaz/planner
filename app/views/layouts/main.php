<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Planificador Kanban') ?></title>
  
  <!-- PWA Meta Tags -->
  <meta name="description" content="Aplicación de gestión de proyectos con tablero Kanban, sprints y bitácora de actividades">
  <meta name="theme-color" content="#3b82f6">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="Kanban">
  
  <!-- Manifest PWA -->
  <link rel="manifest" href="/planificador/public/manifest.json">
  
  <!-- Favicons -->
  <link rel="icon" type="image/png" sizes="192x192" href="/planificador/public/icons/icon-192x192.png">
  <link rel="icon" type="image/png" sizes="512x512" href="/planificador/public/icons/icon-512x512.png">
  <link rel="apple-touch-icon" sizes="180x180" href="/planificador/public/icons/icon-192x192.png">
  <link rel="shortcut icon" href="/planificador/public/icons/favicon.png">
  
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Custom CSS -->
  <link rel="stylesheet" href="public/css/styles.css">
  <link rel="stylesheet" href="public/css/ai-chat.css">
</head>
<body>

<?php include __DIR__ . '/../components/header.php'; ?>

<main class="container-fluid px-3">
  <?php include __DIR__ . '/../home.php'; ?>
</main>

<?php include __DIR__ . '/../components/modals.php'; ?>
<?php include __DIR__ . '/../components/chat-widget.php'; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script>
  const CSRF = <?= json_encode($csrf ?? '') ?>;
  const BASE_URL = '';
  const ASSETS_URL = 'public';
  window.csrf = CSRF; // Global para AI Assistant
</script>
<script src="public/js/ai-cache.js"></script>
<script src="public/js/ai-assistant.js"></script>
<script src="public/js/ai-chat.js"></script>
<script src="public/js/app.js"></script>

<!-- Service Worker Registration -->
<script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('/planificador/sw.js', {
        scope: '/planificador/'
      })
        .then(registration => {
          console.log('✅ Service Worker registrado:', registration.scope);
        })
        .catch(error => {
          console.log('❌ Error al registrar Service Worker:', error);
        });
    });
  }

  // Detectar cuando la app es instalable
  let deferredPrompt;
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    
    // Mostrar botón de instalación personalizado (opcional)
    console.log('💡 La app se puede instalar');
  });

  // Detectar cuando la app fue instalada
  window.addEventListener('appinstalled', () => {
    console.log('✅ PWA instalada correctamente');
    deferredPrompt = null;
  });
</script>

</body>
</html>
