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
        <input type="text" name="name" required minlength="2" maxlength="120" autocomplete="name" data-validate="required|minLength:2" data-error-message="Escribe tu nombre (mínimo 2 caracteres).">
        <small class="error" data-error-for="name"></small>
      </label>
      <label class="field">
        <span>Correo electrónico</span>
        <input type="email" name="email" required autocomplete="email" maxlength="254" data-validate="required|email" data-error-message="Escribe un correo válido, por ejemplo nombre@correo.com.">
        <small class="error" data-error-for="email"></small>
      </label>
      <label class="field">
        <span>Contraseña</span>
        <input type="password" name="password" required minlength="8" maxlength="128" autocomplete="new-password" data-validate="required|minLength:8|pattern:^(?=.*[A-Za-z])(?=.*[0-9]).+$" data-error-message="La contraseña debe tener mínimo 8 caracteres, con al menos una letra y un número.">
        <small class="hint">Mínimo 8 caracteres, incluyendo una letra y un número.</small>
        <small class="error" data-error-for="password"></small>
      </label>
      <button class="btn btn-primary btn-block" type="submit" data-submit>Crear cuenta</button>
      <p class="auth-switch">¿Ya tienes cuenta? <a href="/login.php">Inicia sesión</a></p>
    </form>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>