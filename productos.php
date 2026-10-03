<?php
$pageTitle = 'Catálogo de Productos | Tienda de Artesanías';
$pageDescription = 'Explora nuestro catálogo completo de artesanías colombianas: collares, bolsos, cerámica y más. Filtra por categoría y encuentra tu próxima pieza favorita.';
require __DIR__ . '/partials/header.php';
?>

<section class="page-header">
  <div class="container">
    <h1>Catálogo</h1>
    <p>Explora las piezas únicas que tenemos para ti.</p>
  </div>
</section>

<section class="catalog container">
  <aside class="filters-panel" aria-label="Filtros">
    <form id="filters-form" class="filters-form" autocomplete="off">
      <label class="field">
        <span>Buscar</span>
        <input type="search" name="search" placeholder="Collar, bolso, cerámica…" maxlength="80">
      </label>
      <label class="field">
        <span>Categoría</span>
        <select name="category" data-filter-categories>
          <option value="">Todas</option>
        </select>
      </label>
      <button type="submit" class="btn btn-primary btn-block">Aplicar</button>
      <button type="reset" class="btn btn-ghost btn-block">Limpiar</button>
    </form>
  </aside>

  <div class="catalog-results">
    <div class="catalog-toolbar">
      <p id="catalog-status" class="muted" aria-live="polite">Cargando productos…</p>
      <label class="field inline">
        <span>Por página</span>
        <select name="per_page" data-per-page>
          <option value="12">12</option>
            <option value="24">24</option>
            <option value="48">48</option>
          </select>
        </label>
    </div>
    <div id="product-grid" class="product-grid" aria-busy="true">
      <p class="muted">Cargando…</p>
    </div>
    <nav id="pagination" class="pagination" aria-label="Paginación"></nav>
  </div>
</section>

<template id="product-card-template">
  <article class="product-card">
    <a class="product-media" href="#">
      <img alt="" loading="lazy" decoding="async">
    </a>
    <div class="product-body">
      <p class="product-category" data-cat>—</p>
      <h3 class="product-title"><a href="#"></a></h3>
      <p class="product-price" data-price></p>
      <button class="btn btn-primary btn-block" data-add-to-cart>Agregar al carrito</button>
    </div>
  </article>
</template>

<?php require __DIR__ . '/partials/footer.php'; ?>