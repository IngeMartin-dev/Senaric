<?php
$pageTitle = 'Carrito de Compras | Tienda de Artesanías';
$pageDescription = 'Revisa los productos de tu carrito antes de finalizar la compra.';
require __DIR__ . '/partials/header.php';
?>

<section class="page-header">
  <div class="container">
    <h1>Tu carrito</h1>
    <p id="cart-summary" class="muted" aria-live="polite">Cargando…</p>
  </div>
</section>

<section class="cart container">
  <div class="cart-items" id="cart-items" aria-busy="true">
    <p class="muted">Cargando…</p>
  </div>

  <aside class="cart-summary" aria-label="Resumen de compra">
    <h2>Resumen</h2>
    <dl>
      <div><dt>Subtotal</dt><dd data-subtotal>$0 COP</dd></div>
      <div><dt>Envío</dt><dd data-shipping>$0 COP</dd></div>
      <div class="cart-total"><dt>Total</dt><dd data-total>$0 COP</dd></div>
    </dl>
    <a class="btn btn-primary btn-block" href="/checkout.php">Finalizar compra</a>
    <a class="btn btn-ghost btn-block" href="/productos.php">Seguir comprando</a>
    <button type="button" class="btn btn-danger btn-block" data-clear-cart>Vaciar carrito</button>
  </aside>
</section>

<template id="cart-item-template">
  <article class="cart-item">
    <img alt="" loading="lazy" decoding="async">
    <div class="cart-item-body">
      <h3><a href="#"></a></h3>
      <p class="cart-item-price" data-unit></p>
      <label class="cart-qty">
        <span>Cantidad</span>
        <input type="number" min="1" max="99" value="1" data-qty>
      </label>
    </div>
    <p class="cart-item-total" data-line></p>
    <button class="cart-remove" data-remove aria-label="Quitar del carrito">×</button>
  </article>
</template>

<?php require __DIR__ . '/partials/footer.php'; ?>