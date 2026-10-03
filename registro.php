<?php
$pageTitle = 'Crear Cuenta | Tienda de Artesanías';
$pageDescription = 'Regístrate para comprar más rápido, ver tu historial y guardar tus direcciones.';
require __DIR__ . '/partials/header.php';
?>

<section class="auth container">
  <div class="auth-card">
    <h1>Crea tu cuenta</h1>
    <p class="muted">Te tomará menos de un minuto.</p>
    <form id="register-form" novalidate>
      <input type="hidden" name="_csrf" value="">
      <label class="field">
        <span>Nombre</span>
        <input type="text" name="name" required minlength="2" maxlength="120" autocomplete="name">
        <small class="error" data-error-for="name"></small>
      </label>
      <label class="field">
        <span>Correo electrónico</span>
        <input type="email" name="email" required autocomplete="email" maxlength="254">
        <small class="error" data-error-for="email"></small>
      </label>
      <label class="field">
        <span>Contraseña</span>
        <input type="password" name="password" required minlength="8" maxlength="128" autocomplete="new-password">
        <small class="hint">Mínimo 8 caracteres, incluyendo una letra y un número.</small>
        <small class="error" data-error-for="password"></small>
      </label>
      <button class="btn btn-primary btn-block" type="submit" data-submit>Crear cuenta</button>
      <p class="auth-switch">¿Ya tienes cuenta? <a href="/login.html">Inicia sesión</a></p>
    </form>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>