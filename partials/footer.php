<?php
require_once dirname(__DIR__) . '/src/Helpers/view.php';
/** @var string $appName */
?>
  </main>
  <footer class="site-footer" role="contentinfo">
    <div class="container footer-inner">
      <div>
        <p class="footer-brand"><?= e($appName ?? 'Tienda de Artesanías') ?></p>
        <p class="footer-tag">Artesanía local, hecha a mano con amor.</p>
      </div>
      <nav class="footer-nav" aria-label="Navegación de pie">
        <a href="/">Inicio</a>
        <a href="/productos.php">Productos</a>
        <a href="/contacto.php">Contacto</a>
        <a href="/login.php">Mi cuenta</a>
      </nav>
      <p class="footer-copy">&copy; <?= date('Y') ?> <?= e($appName ?? 'Tienda de Artesanías') ?>.</p>
    </div>
  </footer>
  <div id="toast-region" class="toast-region" aria-live="polite" aria-atomic="true"></div>
  <script src="<?= e(asset_v('/assets/js/api.js')) ?>" defer></script>
  <script src="<?= e(asset_v('/assets/js/ui.js')) ?>" defer></script>
  <script src="<?= e(asset_v('/assets/js/cart.js')) ?>" defer></script>
  <script src="<?= e(asset_v('/assets/js/validator.js')) ?>" defer></script>
  <script src="<?= e(asset_v('/assets/js/app.js')) ?>" defer></script>
</body>
</html>