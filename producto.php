<?php
$pageTitle = 'Producto | Tienda de Artesanías';
$pageDescription = 'Detalle del producto.';
$slug = isset($_GET['slug']) ? (string) $_GET['slug'] : '';
require __DIR__ . '/partials/header.php';
?>

<section class="page-header">
  <div class="container">
    <h1 id="product-title">Cargando…</h1>
    <p class="muted" id="product-category"></p>
  </div>
</section>

<section class="product-detail container" id="product-detail" aria-busy="true">
  <p class="muted">Cargando producto…</p>
</section>

<template id="product-detail-template">
  <article class="product-detail-grid">
    <div class="product-detail-media">
      <img alt="" loading="eager" decoding="async">
    </div>
    <div class="product-detail-body">
      <p class="product-category" data-cat></p>
      <h2 data-name></h2>
      <p class="product-price-large" data-price></p>
      <p class="product-stock" data-stock></p>
      <p class="product-desc" data-short></p>
      <p class="product-desc-long muted" data-desc></p>
      <form id="add-to-cart-form" class="add-to-cart">
        <label class="field inline">
          <span>Cantidad</span>
          <input type="number" name="quantity" min="1" max="99" value="1" data-qty>
        </label>
        <button type="submit" class="btn btn-primary">Agregar al carrito</button>
      </form>
    </div>
  </article>
</template>

<script>
  (async function () {
    const slug = <?= json_encode($slug) ?>;
    const target = document.getElementById('product-detail');
    const titleEl = document.getElementById('product-title');
    const catEl = document.getElementById('product-category');
    if (!slug) {
      target.innerHTML = '<p class="muted">Producto no especificado.</p>';
      return;
    }
    try {
      const res = await window.API.get('/products/' + encodeURIComponent(slug));
      const p = res.data;
      document.title = (p.name || 'Producto') + ' | Tienda de Artesanías';
      titleEl.textContent = p.name;
      catEl.textContent = (p.categories && p.categories.name) || '';
      const tpl = document.getElementById('product-detail-template');
      const node = tpl.content.cloneNode(true);
      node.querySelector('img').src = p.image_url || '/assets/img/placeholder.svg';
      node.querySelector('img').alt = p.name;
      node.querySelector('[data-name]').textContent = p.name;
      node.querySelector('[data-cat]').textContent = (p.categories && p.categories.name) || '';
      node.querySelector('[data-price]').textContent = window.UI.fmt(p.price);
      const infiniteStock = !!(window.APP_CONFIG && window.APP_CONFIG.stockInfinite);
      node.querySelector('[data-stock]').textContent = infiniteStock
        ? 'Disponible'
        : ((p.stock > 0) ? `Stock disponible: ${p.stock}` : 'Sin stock');
      node.querySelector('[data-short]').textContent = p.short_description || '';
      node.querySelector('[data-desc]').textContent = p.description || '';
      target.innerHTML = '';
      target.appendChild(node);
      target.removeAttribute('aria-busy');

      const form = document.getElementById('add-to-cart-form');
      form.addEventListener('submit', (e) => {
        e.preventDefault();
        const qty = parseInt(form.quantity.value, 10) || 1;
        window.CART.add(p.id, qty);
      });
    } catch (e) {
      target.innerHTML = '<p class="muted">No se pudo cargar el producto.</p>';
    }
  })();
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>