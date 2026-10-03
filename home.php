<?php
$pageTitle = 'Tienda de Artesanías | Hecho a mano en Colombia';
$pageDescription = 'Descubre artesanías únicas hechas a mano por artesanos colombianos. Envíos a todo el país, pago contra entrega y atención personalizada.';
$pageCanonical = '/';
require __DIR__ . '/partials/header.php';
?>

<section class="hero">
  <div class="container hero-inner">
    <div class="hero-copy">
      <p class="eyebrow">Artesanía local · Hecho a mano</p>
      <h1 class="hero-title">El arte de lo hecho con las manos.</h1>
      <p class="hero-lead">Cada pieza cuenta una historia. Trabajamos directamente con artesanos colombianos para llevar a tu puerta joyería, textiles y cerámica auténtica.</p>
      <div class="hero-cta">
        <a class="btn btn-primary" href="/productos.php">Explorar catálogo</a>
        <a class="btn btn-ghost" href="/contacto.php">Conversemos</a>
      </div>
      <ul class="hero-trust" role="list">
        <li>🚚 Envíos a toda Colombia</li>
        <li>💵 Pago contra entrega</li>
        <li>🤝 Compra directa al artesano</li>
      </ul>
    </div>
    <div class="hero-visual" aria-hidden="true">
      <div class="hero-card hero-card-1"></div>
      <div class="hero-card hero-card-2"></div>
      <div class="hero-card hero-card-3"></div>
    </div>
  </div>
</section>

<section class="features">
  <div class="container features-grid">
    <article class="feature">
      <div class="feature-icon" aria-hidden="true">🎨</div>
      <h3>Auténtico</h3>
      <p>Piezas únicas creadas por artesanos locales; nada de producción industrial.</p>
    </article>
    <article class="feature">
      <div class="feature-icon" aria-hidden="true">🌿</div>
      <h3>Sostenible</h3>
      <p>Materiales naturales, técnicas tradicionales y comercio justo.</p>
    </article>
    <article class="feature">
      <div class="feature-icon" aria-hidden="true">📦</div>
      <h3>Envío seguro</h3>
      <p>Empaque cuidadoso y seguimiento en todo el país. Envío gratis sobre $100.000 COP.</p>
    </article>
  </div>
</section>

<section class="catalog-preview">
  <div class="container">
    <header class="section-header">
      <h2>Lo más reciente</h2>
      <a class="section-link" href="/productos.php">Ver todo →</a>
    </header>
    <div id="featured-products" class="product-grid" aria-busy="true">
      <p class="muted">Cargando productos destacados…</p>
    </div>
  </div>
</section>

<section class="cta-band">
  <div class="container cta-inner">
    <h2>¿Buscas algo especial?</h2>
    <p>Si tienes una idea en mente o quieres un pedido personalizado, escríbenos y lo hacemos posible.</p>
    <a class="btn btn-primary" href="/contacto.php">Contáctanos</a>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>