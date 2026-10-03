/**
 * Gestión del carrito en cliente.
 * Sincroniza con el backend vía API y actualiza la interfaz al instante:
 *   - contador del icono del carrito (en todas las páginas)
 *   - precio de la línea, subtotal, envío y total al cambiar la cantidad
 */
(function () {
  'use strict';
  const { fmt } = window.UI;

  // Misma regla que CartService::totals() en el backend (solo para la vista previa inmediata;
  // al terminar cada petición se muestran siempre los valores reales del servidor).
  const FREE_SHIPPING_FROM = 100000;
  const SHIPPING_FEE = 9000;
  const SYNC_DELAY_MS = 400;

  const emptyTotals = () => ({ subtotal: 0, shipping: 0, total: 0, count: 0, currency: 'COP' });
  const state = { items: [], totals: emptyTotals() };

  let syncSeq = 0;
  let syncTimer = null;

  /* ------------------------------ Estado ------------------------------ */

  function computeTotals(items) {
    let subtotal = 0;
    let count = 0;
    items.forEach(it => {
      subtotal += Number(it.unit_price || 0) * Number(it.quantity || 0);
      count += Number(it.quantity || 0);
    });
    const shipping = subtotal === 0 || subtotal >= FREE_SHIPPING_FROM ? 0 : SHIPPING_FEE;
    return { subtotal, shipping, total: subtotal + shipping, count, currency: 'COP' };
  }

  /** Aplica una respuesta de la API ({ ok, data: { items, totals } }) al estado. */
  function applyServer(res) {
    const d = res && res.data ? res.data : {};
    state.items = Array.isArray(d) ? d : (Array.isArray(d.items) ? d.items : []);
    state.totals = d && d.totals ? d.totals : computeTotals(state.items);
  }

  function renderAll() {
    updateBadge();
    renderCartPage();
    renderCheckoutSummary();
  }

  /* ------------------------------ Contador ----------------------------- */

  function updateBadge() {
    const count = Number(state.totals.count) || 0;
    document.querySelectorAll('[data-cart-count]').forEach(el => {
      el.textContent = String(count);
      el.style.display = count ? 'inline-block' : 'none';
      el.setAttribute('aria-label', count + (count === 1 ? ' producto' : ' productos') + ' en el carrito');
    });
  }

  /* --------------------------- Página de carrito ----------------------- */

  function maxQty(item) {
    const stock = Number(item.stock);
    return Number.isFinite(stock) && stock > 0 ? Math.min(99, stock) : 99;
  }

  function setSummary() {
    const container = document.getElementById('cart-items');
    if (!container) return;

    const summary = document.getElementById('cart-summary');
    if (summary) {
      const n = Number(state.totals.count) || 0;
      summary.textContent = state.items.length
        ? `${n} producto${n === 1 ? '' : 's'} en tu carrito`
        : 'Aún no has agregado productos.';
    }

    const parent = container.parentElement;
    const sub = parent.querySelector('[data-subtotal]');
    const ship = parent.querySelector('[data-shipping]');
    const total = parent.querySelector('[data-total]');
    if (sub) sub.textContent = fmt(state.totals.subtotal);
    if (ship) ship.textContent = state.totals.shipping ? fmt(state.totals.shipping) : 'Gratis';
    if (total) total.textContent = fmt(state.totals.total);
  }

  function patchRow(row, item) {
    row.querySelector('[data-unit]').textContent = fmt(item.unit_price) + ' c/u';
    row.querySelector('[data-line]').textContent = fmt(item.line_total);
    const input = row.querySelector('[data-qty]');
    input.max = String(maxQty(item));
    // No pisar lo que la persona está escribiendo en ese momento.
    if (document.activeElement !== input) input.value = item.quantity;
  }

  function buildRow(item, tpl) {
    const node = tpl.content.cloneNode(true);
    const row = node.querySelector('.cart-item');
    row.dataset.productId = String(item.product_id);

    const img = node.querySelector('img');
    const link = node.querySelector('h3 a');
    link.textContent = item.name;
    link.href = '/producto.php?slug=' + encodeURIComponent(item.slug || '');
    img.src = item.image_url || '/assets/img/placeholder.svg';
    img.alt = item.name;

    const input = node.querySelector('[data-qty]');
    input.addEventListener('input', () => onQtyInput(item.product_id, input));
    input.addEventListener('change', () => onQtyCommit(item.product_id, input));
    node.querySelector('[data-remove]').addEventListener('click', () => remove(item.product_id));

    patchRow(row, item);
    return node;
  }

  function renderCartPage() {
    const container = document.getElementById('cart-items');
    if (!container) return;
    container.removeAttribute('aria-busy');

    if (!state.items.length) {
      container.innerHTML = '<p class="muted">Tu carrito está vacío. <a href="/productos.php">Explora el catálogo →</a></p>';
      setSummary();
      return;
    }

    const rows = Array.from(container.querySelectorAll('.cart-item'));
    const sameLayout = rows.length === state.items.length &&
      rows.every((r, i) => r.dataset.productId === String(state.items[i].product_id));

    if (sameLayout) {
      // Mismos productos: solo se actualizan los valores (no se pierde el foco del input).
      rows.forEach((row, i) => patchRow(row, state.items[i]));
    } else {
      const tpl = document.getElementById('cart-item-template');
      container.innerHTML = '';
      state.items.forEach(item => container.appendChild(buildRow(item, tpl)));
    }
    setSummary();
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

  /* ------------------------ Cambio de cantidad ------------------------- */

  /** Mientras se escribe: actualiza precios al instante y sincroniza con el servidor sin saturarlo. */
  function onQtyInput(productId, input) {
    const item = state.items.find(i => i.product_id === productId);
    if (!item) return;

    let qty = parseInt(input.value, 10);
    if (!Number.isFinite(qty) || qty < 1) return; // todavía escribiendo; se corrige al salir del campo

    const max = maxQty(item);
    if (qty > max) {
      qty = max;
      input.value = String(max);
      window.UI.toast(`Solo hay ${max} disponible${max === 1 ? '' : 's'} de este producto.`, 'info');
    }

    // Vista previa inmediata
    item.quantity = qty;
    item.line_total = Math.round(Number(item.unit_price || 0) * qty * 100) / 100;
    state.totals = computeTotals(state.items);
    const row = input.closest('.cart-item');
    if (row) row.querySelector('[data-line]').textContent = fmt(item.line_total);
    updateBadge();
    setSummary();
    renderCheckoutSummary();

    clearTimeout(syncTimer);
    syncTimer = setTimeout(() => syncQuantity(productId, qty), SYNC_DELAY_MS);
  }

  /** Al salir del campo: corrige valores vacíos/ inválidos y sincroniza ya. */
  function onQtyCommit(productId, input) {
    const item = state.items.find(i => i.product_id === productId);
    if (!item) return;
    const qty = parseInt(input.value, 10);
    if (!Number.isFinite(qty) || qty < 1) {
      input.value = String(item.quantity);
      return;
    }
    clearTimeout(syncTimer);
    onQtyInput(productId, input);
    clearTimeout(syncTimer);
    syncQuantity(productId, item.quantity);
  }

  async function syncQuantity(productId, quantity) {
    const mine = ++syncSeq;
    try {
      const res = await window.API.put('/cart/update', { product_id: productId, quantity });
      if (mine !== syncSeq) return; // llegó una respuesta más nueva
      applyServer(res);
      renderAll();
    } catch (e) {
      window.UI.toast(e.message || 'No se pudo actualizar.', 'error');
      if (mine === syncSeq) await load(); // volver al estado real del servidor
    }
  }

  /* ------------------------------ Acciones ----------------------------- */

  async function load() {
    try {
      applyServer(await window.API.get('/cart'));
    } catch (e) {
      window.UI.toast('No se pudo cargar el carrito.', 'error');
    }
    renderAll();
  }

  async function add(productId, quantity = 1) {
    try {
      const res = await window.API.post('/cart/add', { product_id: productId, quantity });
      applyServer(res);
      renderAll();
      window.UI.toast('Producto agregado al carrito.', 'success');
    } catch (e) {
      window.UI.toast(e.message || 'No se pudo agregar.', 'error');
    }
  }

  async function update(productId, quantity) {
    syncSeq++;
    clearTimeout(syncTimer);
    return syncQuantity(productId, quantity);
  }

  async function remove(productId) {
    clearTimeout(syncTimer);
    syncSeq++;
    try {
      const res = await window.API.del('/cart/remove?product_id=' + encodeURIComponent(productId));
      applyServer(res);
      renderAll();
    } catch (e) {
      window.UI.toast(e.message || 'No se pudo quitar.', 'error');
    }
  }

  async function clear() {
    clearTimeout(syncTimer);
    syncSeq++;
    try {
      await window.API.del('/cart/clear');
      state.items = [];
      state.totals = emptyTotals();
      renderAll();
      window.UI.toast('Carrito vaciado.', 'info');
    } catch (e) {
      window.UI.toast(e.message || 'No se pudo vaciar.', 'error');
    }
  }

  // Un único listener delegado para todos los botones "Agregar al carrito".
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