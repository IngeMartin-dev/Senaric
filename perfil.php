<?php
$pageTitle = 'Mi Cuenta | Tienda de Artesanías';
$pageDescription = 'Gestiona tus datos y revisa el historial de pedidos.';
require __DIR__ . '/partials/header.php';
?>

<section class="page-header">
  <div class="container">
    <h1>Mi cuenta</h1>
    <p class="muted">Revisa tu información y tus pedidos.</p>
  </div>
</section>

<section class="profile container">
  <article class="profile-card">
    <h2>Datos</h2>
    <p><strong>Correo:</strong> <span data-user-email>—</span></p>
    <p><strong>Nombre:</strong> <span data-user-name>—</span></p>
    <button class="btn btn-ghost" id="logout-btn">Cerrar sesión</button>
  </article>

  <article class="profile-orders">
    <h2>Historial de pedidos</h2>
    <div id="orders-list" aria-busy="true">
      <p class="muted">Cargando pedidos…</p>
    </div>
  </article>
</section>

<template id="order-template">
  <article class="order-card">
    <header>
      <div>
        <h3>Pedido #<span data-id></span></h3>
        <p class="muted small" data-date></p>
      </div>
      <span class="badge" data-status></span>
    </header>
    <ul class="order-items" data-items></ul>
    <footer>
      <span>Total: <strong data-total></strong></span>
    </footer>
  </article>
</template>

<?php require __DIR__ . '/partials/footer.php'; ?>