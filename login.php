<?php
$pageTitle = 'Iniciar Sesión | Tienda de Artesanías';
$pageDescription = 'Ingresa a tu cuenta para gestionar tus pedidos y dirección de envío.';
require __DIR__ . '/partials/header.php';
?>

<section class="auth container">
  <div class="auth-card">
    <h1>Bienvenido de vuelta</h1>
    <p class="muted">Ingresa para continuar con tu compra.</p>
    <form id="login-form" novalidate>
      <input type="hidden" name="_csrf" value="">
      <label class="field">
        <span>Correo electrónico</span>
        <input type="email" name="email" required autocomplete="email" maxlength="254">
        <small class="error" data-error-for="email"></small>
      </label>
      <label class="field">
        <span>Contraseña</span>
        <input type="password" name="password" required minlength="8" maxlength="128" autocomplete="current-password">
        <small class="error" data-error-for="password"></small>
      </label>
      <button class="btn btn-primary btn-block" type="submit" data-submit>Ingresar</button>
      <p class="auth-switch">¿No tienes cuenta? <a href="/registro.html">Crea una aquí</a></p>
    </form>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>