<?php
$pageTitle = 'Administrar categorías';
require dirname(__DIR__) . '/partials/header.php';
?>

<section class="page-header">
  <div class="container admin-page-header">
    <h1>Categorías</h1>
    <button class="btn btn-primary" id="new-category">Nueva categoría</button>
  </div>
</section>

<section class="admin container">
  <table class="admin-table" id="admin-categories">
    <thead>
      <tr><th>Nombre</th><th>Slug</th><th></th></tr>
    </thead>
    <tbody>
      <tr><td colspan="3" class="muted">Cargando…</td></tr>
    </tbody>
  </table>
</section>

<dialog id="category-dialog" class="dialog">
  <form method="dialog" id="category-form">
    <header>
      <h2 id="category-dialog-title">Nueva categoría</h2>
      <button type="button" class="dialog-close" data-close-dialog aria-label="Cerrar">×</button>
    </header>
    <div class="grid-2">
      <label class="field"><span>Nombre</span><input type="text" name="name" required minlength="2" maxlength="120"></label>
      <label class="field"><span>Slug</span><input type="text" name="slug" required pattern="[a-z0-9-]+" maxlength="120"></label>
      <label class="field grid-span-2"><span>Descripción</span><input type="text" name="description" maxlength="280"></label>
    </div>
    <footer>
      <button type="button" class="btn btn-ghost" data-close-dialog>Cancelar</button>
      <button type="submit" class="btn btn-primary">Guardar</button>
    </footer>
  </form>
</dialog>

<?php require dirname(__DIR__) . '/partials/footer.php'; ?>