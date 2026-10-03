<?php
$pageTitle = 'Administrar productos';
require dirname(__DIR__) . '/partials/header.php';
?>

<section class="page-header">
  <div class="container admin-page-header">
    <h1>Productos</h1>
    <button class="btn btn-primary" id="new-product">Nuevo producto</button>
  </div>
</section>

<section class="admin container">
  <table class="admin-table" id="admin-products">
    <thead>
      <tr><th>Producto</th><th>Precio</th><th>Stock</th><th>Estado</th><th></th></tr>
    </thead>
    <tbody>
      <tr><td colspan="5" class="muted">Cargando…</td></tr>
    </tbody>
  </table>
</section>

<dialog id="product-dialog" class="dialog">
  <form method="dialog" id="product-form">
    <header>
      <h2 id="product-dialog-title">Nuevo producto</h2>
      <button type="button" class="dialog-close" data-close-dialog aria-label="Cerrar">×</button>
    </header>
    <div class="grid-2">
      <label class="field">
        <span>Nombre</span>
        <input type="text" name="name" required minlength="2" maxlength="180">
      </label>
      <label class="field">
        <span>Slug</span>
        <input type="text" name="slug" required pattern="[a-z0-9-]+" maxlength="180">
      </label>
      <label class="field">
        <span>Precio (COP)</span>
        <input type="number" name="price" required min="0" step="1">
      </label>
      <label class="field">
        <span>Stock</span>
        <input type="number" name="stock" min="0" step="1" value="0">
      </label>
      <label class="field">
        <span>Categoría</span>
        <select name="category_id" data-admin-categories></select>
      </label>
      <label class="field">
        <span>Imagen (URL)</span>
        <input type="url" name="image_url" maxlength="500" placeholder="https://…">
      </label>
      <label class="field grid-span-2">
        <span>Descripción corta</span>
        <input type="text" name="short_description" maxlength="280">
      </label>
      <label class="field grid-span-2">
        <span>Descripción</span>
        <textarea name="description" rows="4" maxlength="5000"></textarea>
      </label>
      <label class="checkbox">
        <input type="checkbox" name="featured" value="true">
        <span>Destacado</span>
      </label>
      <label class="field">
        <span>Estado</span>
        <select name="status">
          <option value="active">Activo</option>
          <option value="draft">Borrador</option>
          <option value="archived">Archivado</option>
        </select>
      </label>
    </div>
    <footer>
      <button type="button" class="btn btn-ghost" data-close-dialog>Cancelar</button>
      <button type="submit" class="btn btn-primary">Guardar</button>
    </footer>
  </form>
</dialog>

<?php require dirname(__DIR__) . '/partials/footer.php'; ?>