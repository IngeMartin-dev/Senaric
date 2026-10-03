<?php
$pageTitle = 'Contacto | Tienda de Artesanías';
$pageDescription = 'Escríbenos y te respondemos pronto. Estamos para ayudarte con tu pedido o cualquier consulta sobre nuestras artesanías.';
require __DIR__ . '/partials/header.php';
?>

<section class="page-header">
  <div class="container">
    <h1>Contáctanos</h1>
    <p>¿Tienes alguna pregunta o quieres un pedido especial? Escríbenos.</p>
  </div>
</section>

<section class="contact container">
  <form id="contact-form" class="contact-form" novalidate>
    <input type="hidden" name="_csrf" value="">
    <div class="grid-2">
      <label class="field">
        <span>Nombre *</span>
        <input type="text" name="nombre" required minlength="2" maxlength="120" autocomplete="name">
        <small class="error" data-error-for="nombre"></small>
      </label>
      <label class="field">
        <span>Correo *</span>
        <input type="email" name="email" required autocomplete="email" maxlength="254">
        <small class="error" data-error-for="email"></small>
      </label>
      <label class="field">
        <span>Teléfono</span>
        <input type="tel" name="telefono" maxlength="40" autocomplete="tel">
      </label>
      <label class="field">
        <span>Asunto *</span>
        <input type="text" name="asunto" required minlength="3" maxlength="120">
        <small class="error" data-error-for="asunto"></small>
      </label>
      <label class="field grid-span-2">
        <span>Mensaje *</span>
        <textarea name="mensaje" required minlength="10" maxlength="2000" rows="6" placeholder="Cuéntanos en qué te podemos ayudar…"></textarea>
        <small class="error" data-error-for="mensaje"></small>
      </label>
    </div>
    <button class="btn btn-primary" type="submit" data-submit>Enviar mensaje</button>
    <p id="contact-status" class="muted" aria-live="polite"></p>
  </form>

  <aside class="contact-info">
    <h2>Otros canales</h2>
    <p>📍 Bogotá, Colombia</p>
    <p>✉️ hola@tienda-artesanias.co</p>
    <p>📱 +57 300 000 0000</p>
    <p class="muted">Respondemos en horario laboral en menos de 24 horas.</p>
  </aside>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>