/**
 * Gestión del carrito en cliente.
 * Sincroniza con backend vía API.
 */
(function () {
  'use strict';
  const { $, fmt } = window.UI;

  const state = {
    items: [],
    totals: { subtotal: 0, shipping: 0, total: 0, count: 0, currency: 'COP' },
  };

  async function load() {
    try {
      const data = await window.API.get('/cart');
      state.items = data && data.data ? data.data.items || [] : [];
      state.totals = data && data.data ? data.data.totals || state.totals : state.totals;
    } catch (e) {
      window.UI.toast('No se pudo cargar el carrito.', 'error');
    }
    updateBadge();
    renderCartPage();
    renderCheckoutSummary();
  }

  function updateBadge() {
    document.querySelectorAll('[data-cart-count]').forEach(el => {
      el.textContent = state.totals.count || 0;
      el.style.display = state.totals.count ? 'inline-block' : 'none';
    });
  }

  function renderCartPage() {
    const container = document.getElementById('cart-items');
    if (!container) return;

    if (!state.items.length) {
      container.innerHTML = '<p class="muted">Tu carrito está vacío. <a href="/productos.php">Explora el catálogo →</a></p>';
    } else {
      const tpl = document.getElementById('cart-item-template');
      container.innerHTML = '';
      state.items.forEach(item => {
        const node = tpl.content.cloneNode(true);
        const img = node.querySelector('img');
        const link = node.querySelector('h3 a');
        link.textContent = item.name;
        link.href = '/producto.php?slug=' + encodeURIComponent(item.slug || '');
        img.src = item.image_url || '/assets/img/placeholder.svg';
        img.alt = item.name;
        node.querySelector('[data-unit]').textContent = fmt(item.unit_price) + ' c/u';
        node.querySelector('[data-line]').textContent = fmt(item.line_total);
        const qtyInput = node.querySelector('[data-qty]');
        qtyInput.value = item.quantity;
        qtyInput.addEventListener('change', () => update(item.product_id, parseInt(qtyInput.value, 10) || 1));
        node.querySelector('[data-remove]').addEventListener('click', () => remove(item.product_id));
        container.appendChild(node);
      });
    }

    const summary = document.getElementById('cart-summary');
    if (summary) {
      summary.textContent = state.items.length
        ? `${state.totals.count} producto${state.totals.count === 1 ? '' : 's'} en tu carrito`
        : 'Aún no has agregado productos.';
    }

    const parent = container ? container.parentElement : null;
    const sub = parent ? parent.querySelector('[data-subtotal]') : null;
    const ship = parent ? parent.querySelector('[data-shipping]') : null;
    const total = parent ? parent.querySelector('[data-total]') : null;
    if (sub) sub.textContent = fmt(state.totals.subtotal);
    if (ship) ship.textContent = state.totals.shipping ? fmt(state.totals.shipping) : 'Gratis';
    if (total) total.textContent = fmt(state.totals.total);
  }

  function renderCheckoutSummary() {
    const list = document.getElementById('checkout-items');
    if (!list) return;
    if (!state.items.length) {
      list.innerHTML = '<li class="muted">No hay productos en el carrito.</li>';
      return;
    }
    list.innerHTML = state.items.map(it => `
      <li>
        <span>${window.UI.escape(it.name)} <span class="muted">× ${it.quantity}</span></span>
        <strong>${fmt(it.line_total)}</strong>
      </li>
    `).join('');
    const parent = list.parentElement;
    const sub = parent.querySelector('[data-subtotal]');
    const ship = parent.querySelector('[data-shipping]');
    const total = parent.querySelector('[data-total]');
    if (sub) sub.textContent = fmt(state.totals.subtotal);
    if (ship) ship.textContent = state.totals.shipping ? fmt(state.totals.shipping) : 'Gratis';
    if (total) total.textContent = fmt(state.totals.total);
  }

  async function add(productId, quantity = 1) {
    try {
      const res = await window.API.post('/cart/add', { product_id: productId, quantity });
      state.items = res.data.items || [];
      state.totals = res.data.totals || state.totals;
      updateBadge();
      renderCartPage();
      renderCheckoutSummary();
      window.UI.toast('Producto agregado al carrito.', 'success');
    } catch (e) {
      window.UI.toast(e.message || 'No se pudo agregar.', 'error');
    }
  }

  async function update(productId, quantity) {
    try {
      const res = await window.API.put('/cart/update', { product_id: productId, quantity });
      state.items = res.data.items || [];
      state.totals = res.data.totals || state.totals;
      updateBadge();
      renderCartPage();
      renderCheckoutSummary();
    } catch (e) {
      window.UI.toast(e.message || 'No se pudo actualizar.', 'error');
    }
  }

  async function remove(productId) {
    try {
      const res = await window.API.del('/cart/remove?product_id=' + encodeURIComponent(productId));
      state.items = res.data.items || [];
      state.totals = res.data.totals || state.totals;
      updateBadge();
      renderCartPage();
      renderCheckoutSummary();
    } catch (e) {
      window.UI.toast(e.message || 'No se pudo quitar.', 'error');
    }
  }

  async function clear() {
    try {
      await window.API.del('/cart/clear');
      state.items = [];
      state.totals = { subtotal: 0, shipping: 0, total: 0, count: 0, currency: 'COP' };
      updateBadge();
      renderCartPage();
      renderCheckoutSummary();
      window.UI.toast('Carrito vaciado.', 'info');
    } catch (e) {
      window.UI.toast(e.message || 'No se pudo vaciar.', 'error');
    }
  }

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-add-to-cart]');
    if (btn) {
      e.preventDefault();
      const id = parseInt(btn.dataset.productId || btn.closest('[data-product-id]')?.dataset.productId || '0', 10);
      if (id) add(id, 1);
    }
    if (e.target.closest('[data-clear-cart]')) clear();
  });

  window.CART = { add, update, remove, clear, load, state };
})();