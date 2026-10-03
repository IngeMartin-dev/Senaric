<?php
$pageTitle = 'Finalizar Compra | Tienda de Artesanías';
$pageDescription = 'Completa tu pedido y recibe tus artesanías en la puerta de tu casa.';
require __DIR__ . '/partials/header.php';
?>

<section class="page-header">
  <div class="container">
    <h1>Finalizar compra</h1>
    <p class="muted">Revisa tus productos y completa los datos para enviar tu pedido.</p>
  </div>
</section>

<section class="checkout container">
  <form id="checkout-form" class="checkout-form" novalidate>
    <input type="hidden" name="_csrf" value="">

    <fieldset>
      <legend>Datos de envío</legend>
      <div class="grid-2">
        <label class="field">
          <span>Nombre completo *</span>
          <input type="text" name="full_name" required minlength="3" maxlength="120" autocomplete="name">
          <small class="error" data-error-for="full_name"></small>
        </label>
        <label class="field">
          <span>Teléfono *</span>
          <input type="tel" name="phone" required minlength="6" maxlength="40" autocomplete="tel" placeholder="3001234567">
          <small class="error" data-error-for="phone"></small>
        </label>
        <label class="field grid-span-2">
          <span>Dirección *</span>
          <input type="text" name="address" required minlength="8" maxlength="250" autocomplete="street-address" placeholder="Calle 123 #45-67">
            <small class="error" data-error-for="address"></small>
          </label>
        <label class="field">
          <span>Ciudad *</span>
          <input type="text" name="city" required maxlength="80" autocomplete="address-level2">
          <small class="error" data-error-for="city"></small>
        </label>
        <label class="field">
          <span>Departamento *</span>
          <input type="text" name="department" required maxlength="80" autocomplete="address-level1">
          <small class="error" data-error-for="department"></small>
        </label>
        <label class="field grid-span-2">
          <span>Notas para el envío (opcional)</span>
          <textarea name="notes" maxlength="500" rows="3" placeholder="Apto 201, portero, indicaciones especiales…"></textarea>
        </label>
      </div>
    </fieldset>

    <fieldset>
      <legend>Método de pago</legend>
      <label class="radio">
        <input type="radio" name="payment_method" value="cod" checked>
        <span>Pago contra entrega</span>
      </label>
      <label class="radio">
        <input type="radio" name="payment_method" value="transfer">
        <span>Transferencia bancaria</span>
      </label>
    </fieldset>

    <button type="submit" class="btn btn-primary btn-block" data-submit>Confirmar pedido</button>
    <p class="muted small">Al confirmar aceptas nuestros términos y condiciones.</p>
  </form>

  <aside class="checkout-summary" aria-label="Resumen del pedido">
    <h2>Resumen del pedido</h2>
    <ul id="checkout-items" class="checkout-items">
      <li class="muted">Cargando…</li>
    </ul>
    <dl>
      <div><dt>Subtotal</dt><dd data-subtotal>$0 COP</dd></div>
      <div><dt>Envío</dt><dd data-shipping>$0 COP</dd></div>
      <div class="total"><dt>Total</dt><dd data-total>$0 COP</dd></div>
    </dl>
  </aside>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>