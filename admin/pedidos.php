<?php
$pageTitle = 'Administrar pedidos';
require dirname(__DIR__) . '/partials/header.php';
?>

<section class="page-header">
  <div class="container"><h1>Pedidos</h1></div>
</section>

<section class="admin container">
  <table class="admin-table" id="admin-orders">
    <thead>
      <tr><th>#</th><th>Cliente</th><th>Total</th><th>Estado</th><th>Fecha</th></tr>
    </thead>
    <tbody>
      <tr><td colspan="5" class="muted">Cargando…</td></tr>
    </tbody>
  </table>
</section>

<?php require dirname(__DIR__) . '/partials/footer.php'; ?>