<?php
/** @var string $csrfToken */
/** @var string $appName */
/** @var string $appUrl */
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= e($csrfToken ?? '') ?>">
  <meta name="description" content="<?= e($pageDescription ?? 'Artesanías hechas a mano con amor. Envíos a toda Colombia.') ?>">
  <meta name="theme-color" content="#8d6e63">
  <meta property="og:site_name" content="<?= e($appName ?? 'Tienda de Artesanías') ?>">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= e($pageTitle ?? $appName ?? 'Tienda de Artesanías') ?>">
  <meta property="og:description" content="<?= e($pageDescription ?? '') ?>">
  <meta property="og:image" content="<?= e($ogImage ?? '') ?>">
  <meta name="twitter:card" content="summary_large_image">
  <title><?= e($pageTitle ?? $appName ?? 'Tienda de Artesanías') ?></title>
  <link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
  <link rel="stylesheet" href="/assets/css/main.css">
  <link rel="stylesheet" href="/assets/css/components.css">
  <link rel="stylesheet" href="/assets/css/responsive.css">
  <script>
    window.APP_CONFIG = {
      baseUrl: <?= json_encode(rtrim((string)($appUrl ?? ''), '/')) ?>,
      apiBase: <?= json_encode(rtrim((string)($appUrl ?? ''), '/') . '/api') ?>,
      csrfToken: <?= json_encode($csrfToken ?? '') ?>,
      name: <?= json_encode($appName ?? 'Tienda de Artesanías') ?>
    };
  </script>
</head>
<body>
  <a class="skip-link" href="#main">Saltar al contenido principal</a>
  <header class="site-header" role="banner">
    <div class="container header-inner">
      <a class="brand" href="/" aria-label="Inicio">
        <span class="brand-mark" aria-hidden="true">🪶</span>
        <span class="brand-name"><?= e($appName ?? 'Tienda de Artesanías') ?></span>
      </a>

      <button class="nav-toggle" type="button" aria-controls="primary-nav" aria-label="Abrir menú">
        <span></span><span></span><span></span>
      </button>

      <nav class="primary-nav" id="primary-nav" aria-label="Navegación principal">
        <a href="/">Inicio</a>
        <a href="/productos.php">Productos</a>
        <a href="/contacto.php">Contacto</a>
        <a class="nav-cart" href="/carrito.php" aria-label="Ver carrito">
          🛒 Carrito <span class="cart-badge" data-cart-count>0</span>
        </a>
        <a class="nav-auth" href="/login.php">Ingresar</a>
      </nav>
    </div>
  </header>
  <main id="main" class="site-main" role="main">