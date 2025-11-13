<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Planificador Kanban') ?></title>
  
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Custom CSS -->
  <link rel="stylesheet" href="public/css/styles.css">
</head>
<body>

<?php include __DIR__ . '/../components/header.php'; ?>

<main class="container-fluid px-3">
  <?php include __DIR__ . '/../home.php'; ?>
</main>

<?php include __DIR__ . '/../components/modals.php'; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script>
  const CSRF = <?= json_encode($csrf ?? '') ?>;
  const BASE_URL = '';
  const ASSETS_URL = 'public';
</script>
<script src="public/js/app.js"></script>

</body>
</html>
