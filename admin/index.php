<?php
$pageTitle = 'Panel de administración';
$pageDescription = 'Resumen general de la tienda.';
require dirname(__DIR__) . '/partials/header.php';
?>

<section class="page-header">
  <div class="container">
    <h1>Panel de administración</h1>
    <p class="muted">Bienvenido. Desde aquí gestionas el catálogo, las categorías y los pedidos.</p>
  </div>
</section>

<section class="admin-home container">
  <div class="admin-cards">
    <a class="admin-card" href="/admin/productos.php">
      <h3>Catálogo</h3>
      <p>Crear, editar y eliminar productos. Subir imágenes y controlar stock.</p>
    </a>
    <a class="admin-card" href="/admin/pedidos.php">
      <h3>Pedidos</h3>
      <p>Revisar y actualizar el estado de los pedidos entrantes.</p>
    </a>
    <a class="admin-card" href="/admin/categorias.php">
      <h3>Categorías</h3>
      <p>Administrar las categorías del catálogo.</p>
    </a>
  </div>
</section>

<?php require dirname(__DIR__) . '/partials/footer.php'; ?>