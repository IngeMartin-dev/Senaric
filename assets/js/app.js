/**
 * Bootstrap general: carga carrito, configura UI, monta handlers
 * específicos de cada página.
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', () => {
    const csrf = window.APP_CONFIG && window.APP_CONFIG.csrfToken;
    if (csrf) {
      document.querySelectorAll('input[name="_csrf"]').forEach(i => { i.value = csrf; });
    }

    if (window.CART) window.CART.load();

    mountHome();
    mountCatalog();
    mountContact();
    mountCheckout();
    mountAuth();
    mountProfile();
    mountAdmin();
  });

  async function mountHome() {
    const target = document.getElementById('featured-products');
    if (!target) return;
    try {
      const res = await window.API.get('/products?featured=true&per_page=4');
      const items = (res.data && res.data.items) || [];
      if (!items.length) {
        target.innerHTML = '<p class="muted">Pronto tendremos productos destacados.</p>';
        return;
      }
      const tpl = document.getElementById('product-card-template');
      target.innerHTML = '';
      items.forEach(p => target.appendChild(buildCard(p, tpl)));
    } catch (_) {
      target.innerHTML = '<p class="muted">No se pudieron cargar los productos.</p>';
    }
  }

  let catalogState = { page: 1, perPage: 12, category: '', search: '' };

  async function mountCatalog() {
    const grid = document.getElementById('product-grid');
    if (!grid) return;

    try {
      const cats = await window.API.get('/categories');
      const sel = document.querySelector('[data-filter-categories]');
      if (sel && cats.data) {
        cats.data.forEach(c => {
          const opt = document.createElement('option');
          opt.value = c.id; opt.textContent = c.name;
          sel.appendChild(opt);
        });
      }
    } catch (_) {}

    const form = document.getElementById('filters-form');
    form?.addEventListener('submit', (e) => {
      e.preventDefault();
      const fd = new FormData(form);
      catalogState.category = fd.get('category') || '';
      catalogState.search = fd.get('search') || '';
      catalogState.page = 1;
      loadCatalog();
    });
    form?.addEventListener('reset', () => {
      setTimeout(() => { catalogState = { page: 1, perPage: 12, category: '', search: '' }; loadCatalog(); }, 0);
    });

    const perSel = document.querySelector('[data-per-page]');
    perSel?.addEventListener('change', () => {
      catalogState.perPage = parseInt(perSel.value, 10) || 12;
      catalogState.page = 1;
      loadCatalog();
    });

    loadCatalog();
  }

  async function loadCatalog() {
    const grid = document.getElementById('product-grid');
    const status = document.getElementById('catalog-status');
    const pagination = document.getElementById('pagination');
    if (!grid) return;
    grid.setAttribute('aria-busy', 'true');
    if (status) status.textContent = 'Cargando productos…';
    if (pagination) pagination.innerHTML = '';

    const params = new URLSearchParams({
      page: catalogState.page,
      per_page: catalogState.perPage,
    });
    if (catalogState.category) params.set('category', catalogState.category);
    if (catalogState.search) params.set('search', catalogState.search);

    try {
      const res = await window.API.get('/products?' + params.toString());
      const items = (res.data && res.data.items) || [];
      const total = (res.data && res.data.total) || items.length;
      const tpl = document.getElementById('product-card-template');
      grid.innerHTML = '';
      if (!items.length) {
        grid.innerHTML = '<p class="muted">No encontramos productos con esos filtros.</p>';
      } else {
        items.forEach(p => grid.appendChild(buildCard(p, tpl)));
      }
      if (status) status.textContent = `Mostrando ${items.length} de ${total} productos.`;
      renderPagination(total);
    } catch (e) {
      grid.innerHTML = '<p class="muted">Error al cargar productos.</p>';
      window.UI.toast(e.message, 'error');
    } finally {
      grid.removeAttribute('aria-busy');
    }
  }

  function renderPagination(total) {
    const pagination = document.getElementById('pagination');
    if (!pagination) return;
    const totalPages = Math.max(1, Math.ceil(total / catalogState.perPage));
    pagination.innerHTML = '';
    const mkBtn = (label, page, opts = {}) => {
      const b = document.createElement('button');
      b.type = 'button';
      b.textContent = label;
      if (opts.current) b.setAttribute('aria-current', 'page');
      if (opts.disabled) b.disabled = true;
      b.addEventListener('click', () => { catalogState.page = page; loadCatalog(); window.scrollTo({ top: 0, behavior: 'smooth' }); });
      return b;
    };
    pagination.appendChild(mkBtn('«', catalogState.page - 1, { disabled: catalogState.page <= 1 }));
    for (let p = 1; p <= totalPages; p++) {
      pagination.appendChild(mkBtn(String(p), p, { current: p === catalogState.page }));
    }
    pagination.appendChild(mkBtn('»', catalogState.page + 1, { disabled: catalogState.page >= totalPages }));
  }

  function buildCard(product, tpl) {
    const node = tpl.content.cloneNode(true);
    const img = node.querySelector('img');
    img.src = product.image_url || '/assets/img/placeholder.svg';
    img.alt = product.name;
    const link = node.querySelector('.product-title a');
    link.textContent = product.name;
    link.href = '/producto.php?slug=' + encodeURIComponent(product.slug || '');
    node.querySelector('[data-cat]').textContent = (product.categories && product.categories.name) || '—';
    node.querySelector('[data-price]').textContent = window.UI.fmt(product.price);
    const btn = node.querySelector('[data-add-to-cart]');
    btn.dataset.productId = product.id;
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      window.CART.add(product.id, 1);
    });
    return node;
  }

  function mountContact() {
    const form = document.getElementById('contact-form');
    if (!form) return;
    form.classList.add('validate');
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const data = Object.fromEntries(new FormData(form).entries());
      const status = document.getElementById('contact-status');
      try {
        await window.API.post('/contact', data);
        status.textContent = '¡Gracias! Te responderemos pronto.';
        form.reset();
        window.UI.toast('Mensaje enviado correctamente.', 'success');
      } catch (err) {
        status.textContent = err.message || 'No se pudo enviar el mensaje.';
        window.UI.toast(err.message, 'error');
      }
    });
  }

  function mountCheckout() {
    const form = document.getElementById('checkout-form');
    if (!form) return;
    form.classList.add('validate');
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const submit = form.querySelector('[data-submit]');
      submit.disabled = true; submit.textContent = 'Enviando…';
      try {
        const data = Object.fromEntries(new FormData(form).entries());
        const res = await window.API.post('/checkout', data);
        window.UI.toast('Pedido recibido. Número #' + res.data.id, 'success', 5000);
        setTimeout(() => { window.location.href = '/perfil.php'; }, 1200);
      } catch (err) {
        window.UI.toast(err.message || 'No se pudo procesar el pedido.', 'error');
      } finally {
        submit.disabled = false; submit.textContent = 'Confirmar pedido';
      }
    });
  }

  function mountAuth() {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');

    if (loginForm) {
      loginForm.classList.add('validate');
      loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submit = loginForm.querySelector('[data-submit]');
        submit.disabled = true; submit.textContent = 'Ingresando…';
        try {
          const data = Object.fromEntries(new FormData(loginForm).entries());
          const res = await window.API.post('/auth/login', data);
          if (res.data && res.data.access_token) {
            localStorage.setItem('supabase_access_token', res.data.access_token);
            window.UI.toast('Bienvenido.', 'success');
            setTimeout(() => { window.location.href = '/perfil.php'; }, 600);
          }
        } catch (err) {
          window.UI.toast(err.message || 'No se pudo iniciar sesión.', 'error');
        } finally {
          submit.disabled = false; submit.textContent = 'Ingresar';
        }
      });
    }

    if (registerForm) {
      registerForm.classList.add('validate');
      registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submit = registerForm.querySelector('[data-submit]');
        submit.disabled = true; submit.textContent = 'Creando…';
        try {
          const data = Object.fromEntries(new FormData(registerForm).entries());
          const res = await window.API.post('/auth/register', data);
          window.UI.toast(res.data && res.data.access_token ? 'Cuenta creada, bienvenido.' : 'Revisa tu correo para confirmar.', 'success', 5000);
          if (res.data && res.data.access_token) {
            localStorage.setItem('supabase_access_token', res.data.access_token);
            setTimeout(() => { window.location.href = '/perfil.php'; }, 800);
          }
        } catch (err) {
          window.UI.toast(err.message || 'No se pudo crear la cuenta.', 'error');
        } finally {
          submit.disabled = false; submit.textContent = 'Crear cuenta';
        }
      });
    }
  }

  async function mountProfile() {
    const list = document.getElementById('orders-list');
    if (!list) return;
    try {
      const res = await window.API.get('/orders/mine');
      const orders = (res.data && res.data.items) || [];
      if (!orders.length) {
        list.innerHTML = '<p class="muted">Aún no tienes pedidos.</p>';
      } else {
        const tpl = document.getElementById('order-template');
        list.innerHTML = '';
        orders.forEach(o => {
          const node = tpl.content.cloneNode(true);
          node.querySelector('[data-id]').textContent = o.id;
          node.querySelector('[data-date]').textContent = new Date(o.created_at).toLocaleString('es-CO');
          const badge = node.querySelector('[data-status]');
          badge.textContent = o.status;
          badge.className = 'badge ' + (o.status || '');
          const items = node.querySelector('[data-items]');
          (o.items || []).forEach(it => {
            const li = document.createElement('li');
            li.innerHTML = `<span>${window.UI.escape(it.product_name)} × ${it.quantity}</span><span>${window.UI.fmt(it.subtotal)}</span>`;
            items.appendChild(li);
          });
          node.querySelector('[data-total]').textContent = window.UI.fmt(o.total);
          list.appendChild(node);
        });
      }
    } catch (e) {
      list.innerHTML = '<p class="muted">Inicia sesión para ver tus pedidos.</p>';
    }

    const logoutBtn = document.getElementById('logout-btn');
    logoutBtn?.addEventListener('click', async () => {
      try { await window.API.post('/auth/logout'); } catch (_) {}
      localStorage.removeItem('supabase_access_token');
      window.UI.toast('Sesión cerrada.', 'info');
      setTimeout(() => { window.location.href = '/'; }, 500);
    });
  }

  function mountAdmin() {
    const isAdmin = location.pathname.startsWith('/admin');
    if (!isAdmin) return;

    const productsTable = document.querySelector('#admin-products tbody');
    if (productsTable) {
      const newBtn = document.getElementById('new-product');
      const dialog = document.getElementById('product-dialog');
      const form = document.getElementById('product-form');
      const title = document.getElementById('product-dialog-title');

      async function loadCategories() {
        try {
          const res = await window.API.get('/categories');
          const sel = dialog.querySelector('[data-admin-categories]');
          if (sel) {
            sel.innerHTML = '<option value="">Sin categoría</option>';
            (res.data || []).forEach(c => {
              const opt = document.createElement('option');
              opt.value = c.id; opt.textContent = c.name;
              sel.appendChild(opt);
            });
          }
        } catch (_) {}
      }
      loadCategories();

      async function loadProducts() {
        productsTable.innerHTML = '<tr><td colspan="5" class="muted">Cargando…</td></tr>';
        try {
          const res = await window.API.get('/admin/products?per_page=100');
          const rows = (res.data && res.data.items) || [];
          if (!rows.length) {
            productsTable.innerHTML = '<tr><td colspan="5" class="muted">Sin productos aún.</td></tr>';
            return;
          }
          productsTable.innerHTML = '';
          rows.forEach(p => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
              <td>${window.UI.escape(p.name)}<br><small class="muted">${window.UI.escape(p.slug)}</small></td>
              <td>${window.UI.fmt(p.price)}</td>
              <td>${p.stock}</td>
              <td><span class="badge ${p.status}">${window.UI.escape(p.status || 'active')}</span></td>
              <td>
                <button class="btn btn-ghost" data-edit="${p.id}">Editar</button>
                <button class="btn btn-danger" data-delete="${p.id}">Eliminar</button>
              </td>`;
            productsTable.appendChild(tr);
          });
        } catch (e) {
          productsTable.innerHTML = `<tr><td colspan="5" class="muted">${window.UI.escape(e.message)}</td></tr>`;
        }
      }

      newBtn?.addEventListener('click', () => {
        form.reset();
        form.dataset.id = '';
        title.textContent = 'Nuevo producto';
        if (typeof dialog.showModal === 'function') dialog.showModal();
      });

      dialog?.addEventListener('click', (e) => {
        if (e.target.matches('[data-close-dialog]')) dialog.close();
      });

      productsTable?.addEventListener('click', async (e) => {
        const edit = e.target.closest('[data-edit]');
        const del = e.target.closest('[data-delete]');
        if (edit) {
          const id = edit.dataset.edit;
          const res = await window.API.get('/admin/products/' + id);
          const p = res.data;
          form.dataset.id = id;
          form.name.value = p.name || '';
          form.slug.value = p.slug || '';
          form.price.value = p.price || 0;
          form.stock.value = p.stock || 0;
          form.category_id.value = p.category_id || '';
          form.image_url.value = p.image_url || '';
          form.short_description.value = p.short_description || '';
          form.description.value = p.description || '';
          form.featured.checked = !!p.featured;
          form.status.value = p.status || 'active';
          title.textContent = 'Editar producto';
          if (typeof dialog.showModal === 'function') dialog.showModal();
        }
        if (del) {
          if (!confirm('¿Eliminar este producto?')) return;
          try {
            await window.API.del('/admin/products/' + del.dataset.delete);
            window.UI.toast('Producto eliminado.', 'success');
            loadProducts();
          } catch (err) {
            window.UI.toast(err.message, 'error');
          }
        }
      });

      form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = form.dataset.id;
        const payload = {
          name: form.name.value,
          slug: form.slug.value,
          price: parseFloat(form.price.value),
          stock: parseInt(form.stock.value, 10) || 0,
          category_id: form.category_id.value ? parseInt(form.category_id.value, 10) : null,
          image_url: form.image_url.value,
          short_description: form.short_description.value,
          description: form.description.value,
          featured: form.featured.checked,
          status: form.status.value,
        };
        try {
          if (id) {
            await window.API.patch('/admin/products/' + id, payload);
            window.UI.toast('Producto actualizado.', 'success');
          } else {
            await window.API.post('/admin/products', payload);
            window.UI.toast('Producto creado.', 'success');
          }
          dialog.close();
          loadProducts();
        } catch (err) {
          window.UI.toast(err.message || 'Error al guardar.', 'error');
        }
      });

      loadProducts();
    }

    const ordersTable = document.querySelector('#admin-orders tbody');
    if (ordersTable) {
      (async () => {
        ordersTable.innerHTML = '<tr><td colspan="5" class="muted">Cargando…</td></tr>';
        try {
          const res = await window.API.get('/admin/orders?per_page=100');
          const rows = (res.data && res.data.items) || [];
          if (!rows.length) {
            ordersTable.innerHTML = '<tr><td colspan="5" class="muted">Sin pedidos aún.</td></tr>';
            return;
          }
          ordersTable.innerHTML = '';
          rows.forEach(o => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
              <td>#${o.id}</td>
              <td>${window.UI.escape(o.full_name || '')}<br><small class="muted">${window.UI.escape(o.customer_email || '')}</small></td>
              <td>${window.UI.fmt(o.total)}</td>
              <td>
                <select data-status="${o.id}">
                  ${['pending','confirmed','shipped','delivered','cancelled'].map(s => `<option value="${s}" ${s===o.status?'selected':''}>${s}</option>`).join('')}
                </select>
              </td>
              <td>${new Date(o.created_at).toLocaleString('es-CO')}</td>`;
            ordersTable.appendChild(tr);
          });
          ordersTable.addEventListener('change', async (e) => {
            const sel = e.target.closest('[data-status]');
            if (!sel) return;
            try {
              await window.API.patch('/admin/orders/' + sel.dataset.status, { status: sel.value });
              window.UI.toast('Estado actualizado.', 'success');
            } catch (err) {
              window.UI.toast(err.message, 'error');
            }
          });
        } catch (e) {
          ordersTable.innerHTML = `<tr><td colspan="5" class="muted">${window.UI.escape(e.message)}</td></tr>`;
        }
      })();
    }

    const catTable = document.querySelector('#admin-categories tbody');
    if (catTable) {
      const newBtn = document.getElementById('new-category');
      const dialog = document.getElementById('category-dialog');
      const form = document.getElementById('category-form');
      const title = document.getElementById('category-dialog-title');

      async function load() {
        catTable.innerHTML = '<tr><td colspan="3" class="muted">Cargando…</td></tr>';
        try {
          const res = await window.API.get('/categories');
          const rows = res.data || [];
          if (!rows.length) {
            catTable.innerHTML = '<tr><td colspan="3" class="muted">Sin categorías aún.</td></tr>';
            return;
          }
          catTable.innerHTML = '';
          rows.forEach(c => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
              <td>${window.UI.escape(c.name)}</td>
              <td><code>${window.UI.escape(c.slug)}</code></td>
              <td>
                <button class="btn btn-ghost" data-edit="${c.id}">Editar</button>
                <button class="btn btn-danger" data-delete="${c.id}">Eliminar</button>
              </td>`;
            catTable.appendChild(tr);
          });
        } catch (e) {
          catTable.innerHTML = `<tr><td colspan="3" class="muted">${window.UI.escape(e.message)}</td></tr>`;
        }
      }

      newBtn?.addEventListener('click', () => { form.reset(); form.dataset.id=''; title.textContent='Nueva categoría'; if (dialog.showModal) dialog.showModal(); });
      dialog?.addEventListener('click', (e) => { if (e.target.matches('[data-close-dialog]')) dialog.close(); });
      catTable?.addEventListener('click', async (e) => {
        const edit = e.target.closest('[data-edit]');
        const del = e.target.closest('[data-delete]');
        if (edit) {
          const id = edit.dataset.edit;
          const res = await window.API.get('/categories');
          const c = (res.data || []).find(x => String(x.id) === String(id));
          if (c) {
            form.dataset.id = id;
            form.name.value = c.name;
            form.slug.value = c.slug;
            form.description.value = c.description || '';
            title.textContent = 'Editar categoría';
            if (dialog.showModal) dialog.showModal();
          }
        }
        if (del) {
          if (!confirm('¿Eliminar esta categoría?')) return;
          try {
            await window.API.del('/admin/categories/' + del.dataset.delete);
            window.UI.toast('Categoría eliminada.', 'success');
            load();
          } catch (err) { window.UI.toast(err.message, 'error'); }
        }
      });
      form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = form.dataset.id;
        const payload = {
          name: form.name.value,
          slug: form.slug.value,
          description: form.description.value,
        };
        try {
          if (id) await window.API.patch('/admin/categories/' + id, payload);
          else await window.API.post('/admin/categories', payload);
          dialog.close();
          window.UI.toast('Categoría guardada.', 'success');
          load();
        } catch (err) { window.UI.toast(err.message, 'error'); }
      });
      load();
    }
  }
})();